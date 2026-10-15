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

        $decision->setEtat(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);
        $this->decisionAmenagementExamensRepository->save($decision, true);

        //ici on veut aussi rafraichir le cache de l'utilisateur et de la decision elle-même
        $utilisateurResource = new \App\ApiResource\Utilisateur($beneficiaire);
        $this->messageBus->dispatch(new RessourceModifieeMessage($utilisateurResource));
        //si l'état n'a pas changé, le contenu du document a changé quand même !
        $decisionResource = new \App\ApiResource\DecisionAmenagementExamens($decision);
        $this->messageBus->dispatch(new RessourceModifieeMessage($decisionResource));
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
