<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\State\DecisionAmenagementExamens;

use App\Entity\AvisEse;
use App\Entity\Beneficiaire;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\Utilisateur;
use App\Message\RessourceModifieeMessage;
use App\Message\SignatureDecisionDemandeeMessage;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Service\Signature\SignatureElectronique;
use App\State\Utilisateur\UtilisateurManager;
use App\Util\AnneeUniversitaireAwareTrait;
use DateTime;
use DateTimeInterface;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class DecisionAmenagementManager
{
    use ClockAwareTrait;
    use AnneeUniversitaireAwareTrait;

    public function __construct(
        private readonly DecisionAmenagementExamensRepository $decisionAmenagementExamensRepository,
        private readonly UtilisateurManager $utilisateurManager,
        private readonly MessageBusInterface $messageBus,
        private readonly SignatureElectronique $signatureElectronique,
    ) {}

    public function parUidEtAnnee(string $uid, int $annee): ?DecisionAmenagementExamens
    {
        $debutAnnee = $annee;
        $debutAnnee .= '-09-01';
        return $this->decisionAmenagementExamensRepository->findOneBy([
            'beneficiaire' => $this->utilisateurManager->parUid($uid),
            'debut' => new DateTime($debutAnnee),
        ]);
    }

    /**
     * @param Utilisateur $beneficiaire
     * @param DateTimeInterface $debutPeriode
     * @param DateTimeInterface $finPeriode
     * @return void
     */
    public function majEtatDecision(
        Utilisateur $beneficiaire,
        DateTimeInterface $debutPeriode,
        DateTimeInterface $finPeriode,
    ): void {
        /**
         * Appelé si:
         * - avis ESE mis à jour
         * - aménagement d'examen mis à jour
         */

        $decision = $beneficiaire->getDecisionAmenagementExamens($debutPeriode, $finPeriode);

        // en signature, le parapheur rendra la décision signée ou refusée ; refusée, le gestionnaire la reprendra
        if (in_array($decision?->getEtat(), [DecisionAmenagementExamens::ETAT_EN_SIGNATURE, DecisionAmenagementExamens::ETAT_REFUSEE], true)) {
            return;
        }

        $now = $this->now();
        $dateConsideree = match (true) {
            $now >= $debutPeriode && $now <= $finPeriode => $now,
            default => $debutPeriode,
        };

        $amenagementsEnCours = array_filter($beneficiaire->getAmenagementsActifs(), fn($amenagement) => $amenagement
            ->getType()
            ->isDecision());

        //si pas d'avis ESE en cours ni d'aménagement pour la décision en cours pour la période, pas de décision!
        if (
            $beneficiaire->getEtatAvisEse($dateConsideree) !== AvisEse::ETAT_EN_COURS
            && count($amenagementsEnCours) === 0
        ) {
            // décision existante, si supprimable on supprime
            if (null !== $decision && $decision->getEtat() !== DecisionAmenagementExamens::ETAT_EDITE) {
                $this->decisionAmenagementExamensRepository->remove($decision, true);
            }
            return;
        }

        //si avis ESE ou aménagement pour la décision en cours, on veut pouvoir valider/revalider pour édition!

        //création?
        if (null === $decision) {
            $decision = new DecisionAmenagementExamens();
            $decision->setBeneficiaire($beneficiaire);
            $decision->setDebut($debutPeriode);
            $decision->setFin($finPeriode);
            $decision->setDateModification($this->now());
        }

        // le prochain envoi peut se faire sans parapheur : la signature du document précédent ne doit plus s'afficher
        $decision
            ->setEtat(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS)
            ->setEtatSignature(null)
            ->setIdDocumentParapheur(null)
            ->setUidDemandeurSignature(null)
            ->setDerniereVerificationSignature(null)
            ->setDateSignature(null);
        //si l'état n'a pas changé, le contenu du document a changé quand même !
        $this->enregistrer($decision);
    }

    // modification par le gestionnaire ou l'administrateur (Patch de la décision)
    public function modifier(
        DecisionAmenagementExamens $decision,
        string $etat,
        ?string $observations,
        ?DateTimeInterface $dateAvisMedecin,
    ): void {
        $decision
            ->setEtat($etat)
            ->setObservations($observations)
            ->setDateAvisMedecin($dateAvisMedecin);
        $this->enregistrer($decision);
    }

    /**
     * Fait signer la décision validée ou envoyée quand la composante du bénéficiaire a un circuit : elle passe en
     * signature, donc verrouillée dès la demande, et le dépôt se fait en asynchrone. Sans circuit, rien ne change.
     *
     * @return bool vrai si la décision part en signature
     */
    public function demanderSignature(DecisionAmenagementExamens $decision, string $uidDemandeur): bool
    {
        if (null === $this->signatureElectronique->circuitPour($decision)) {
            return false;
        }

        // sans document dans le parapheur, la décision en signature est en cours de dépôt
        $decision
            ->setEtat(DecisionAmenagementExamens::ETAT_EN_SIGNATURE)
            ->setEtatSignature(null)
            ->setIdDocumentParapheur(null);
        $this->enregistrer($decision);
        $this->messageBus->dispatch(new SignatureDecisionDemandeeMessage($decision->getId(), $uidDemandeur));

        return true;
    }

    /**
     * @param string $uidDemandeur auteur de la pièce jointe au retour de signature
     */
    public function noterDepot(DecisionAmenagementExamens $decision, string $documentId, string $uidDemandeur): void
    {
        $decision
            ->setEtat(DecisionAmenagementExamens::ETAT_EN_SIGNATURE)
            ->setEtatSignature(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE)
            ->setIdDocumentParapheur($documentId)
            ->setUidDemandeurSignature($uidDemandeur)
            // une signature précédente ne concerne pas ce nouveau document
            ->setDateSignature(null)
            ->setDerniereVerificationSignature(null);
        $this->enregistrer($decision);
    }

    // circuit retiré de la composante, ou parapheur désactivé, depuis la demande : l'administrateur l'enverra
    public function renoncerSignature(DecisionAmenagementExamens $decision): void
    {
        $decision->setEtat(DecisionAmenagementExamens::ETAT_VALIDE);
        $this->enregistrer($decision);
    }

    // parapheur interrogé, circuit toujours en cours ou document pas encore récupéré
    public function noterVerificationSignature(DecisionAmenagementExamens $decision): void
    {
        $decision->setDerniereVerificationSignature($this->now());
        $this->enregistrer($decision);
    }

    public function noterSignature(DecisionAmenagementExamens $decision, ?DateTimeInterface $dateSignature): void
    {
        $decision
            ->setEtat(DecisionAmenagementExamens::ETAT_EDITE)
            ->setEtatSignature(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE)
            ->setDateSignature($dateSignature)
            ->setDerniereVerificationSignature($this->now());
        $this->enregistrer($decision);
    }

    // rendue sans signature (refusée, expirée, document perdu) : le gestionnaire la reprendra
    public function noterRetourSansSignature(DecisionAmenagementExamens $decision, string $etatSignature): void
    {
        $decision
            ->setEtat(DecisionAmenagementExamens::ETAT_REFUSEE)
            ->setEtatSignature($etatSignature)
            ->setDerniereVerificationSignature($this->now());
        $this->enregistrer($decision);
    }

    // la décision en cours fait partie de la ressource Utilisateur : les deux sont à rafraîchir
    private function enregistrer(DecisionAmenagementExamens $decision): void
    {
        $this->decisionAmenagementExamensRepository->save($decision, true);
        $this->messageBus->dispatch(new RessourceModifieeMessage(new \App\ApiResource\Utilisateur($decision->getBeneficiaire())));
        $this->messageBus->dispatch(new RessourceModifieeMessage(new \App\ApiResource\DecisionAmenagementExamens($decision)));
    }

    public function getDecisionCourante(Utilisateur $utilisateur): ?DecisionAmenagementExamens
    {
        $bornes = $this->bornesAnneeDuJour();
        return $utilisateur->getDecisionAmenagementExamens($bornes['debut'], $bornes['fin']);
    }

    // ressource de l'API, avec l'exigence de la date de l'avis médical que l'interface applique comme le serveur
    public function versRessource(DecisionAmenagementExamens $decision): \App\ApiResource\DecisionAmenagementExamens
    {
        $ressource = new \App\ApiResource\DecisionAmenagementExamens($decision);
        $ressource->dateAvisMedecinRequise = $this->dateAvisMedecinRequise($decision);
        $ressource->motifSignatureImpossible = $this->signatureElectronique->motifSansCircuit($decision);

        return $ressource;
    }

    /**
     * L'édition exige la date de l'avis médical dès qu'un profil de handicap du bénéficiaire, sur la période
     * de la décision, active l'option (ProfilBeneficiaire::avisMedicalRequis).
     */
    public function dateAvisMedecinRequise(DecisionAmenagementExamens $decision): bool
    {
        $beneficiaire = $decision->getBeneficiaire();
        if (null === $beneficiaire || null === $decision->getDebut() || null === $decision->getFin()) {
            return false;
        }

        // accompagnés ou non : la décision reprend les aménagements de tous les profils actifs
        $profils = $beneficiaire->getBeneficiairesParIntervalle(
            $decision->getDebut(),
            $decision->getFin(),
            avecAccompagnement: false,
        );

        return array_any(
            $profils,
            // l'option ne vaut que pour un profil de handicap, celui qui porte une typologie
            fn(Beneficiaire $profil) => true === $profil->getProfil()?->isAvecTypologie()
                && true === $profil->getProfil()->isAvisMedicalRequis(),
        );
    }

    /**
     * Pourquoi les aménagements repris dans la décision et les avis de santé du bénéficiaire ne peuvent pas
     * être modifiés, null s'ils le peuvent. En signature, le document signé ne dirait plus ce qu'affiche
     * OASIS ; refusée, la décision attend d'être reprise par un gestionnaire.
     */
    public function motifVerrou(Utilisateur $beneficiaire): ?string
    {
        foreach ($beneficiaire->getDecisionsAmenagementExamens() as $decision) {
            // une décision d'une année terminée ne reprend plus rien de modifiable
            if ($decision->getFin() < $this->now()) {
                continue;
            }

            $motif = match ($decision->getEtat()) {
                DecisionAmenagementExamens::ETAT_EN_SIGNATURE => 'La décision d\'aménagements de ce bénéficiaire est en cours de signature électronique : '
                    . 'ses aménagements et ses avis de santé ne peuvent pas être modifiés avant la fin du circuit.',
                DecisionAmenagementExamens::ETAT_REFUSEE => 'La décision d\'aménagements de ce bénéficiaire a été refusée dans le circuit de signature : '
                    . 'reprenez-la avant de modifier ses aménagements et ses avis de santé.',
                default => null,
            };
            if (null !== $motif) {
                return $motif;
            }
        }

        return null;
    }
}
