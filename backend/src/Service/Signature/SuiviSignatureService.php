<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

namespace App\Service\Signature;

use App\Entity\DecisionAmenagementExamens;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Service\Decision\ArchivageDecision;
use App\Service\MailService;
use App\State\Utilisateur\UtilisateurManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * Suit les décisions en signature et dépose le PDF rendu par le parapheur au dossier, signé ou
 * refusé. Une décision ne sort de la signature qu'une fois ce PDF archivé, sinon elle est reprise
 * au passage suivant.
 */
readonly class SuiviSignatureService
{
    use ClockAwareTrait;

    public function __construct(
        private DecisionAmenagementExamensRepository $decisionAmenagementExamensRepository,
        private AbstractParapheur $parapheur,
        private ArchivageDecision $archivageDecision,
        private UtilisateurManager $utilisateurManager,
        private LoggerInterface $logger,
        private MailService $mailService,
        // faux : le parapheur n'envoie pas le document signé, OASIS l'envoie comme une décision non signée
        #[Autowire('%env(bool:default::PARAPHEUR_ENVOIE_DOCUMENT)%')]
        private bool $parapheurEnvoieDocument = false,
    ) {}

    public function traiterLot(int $limite = 50): RapportSuiviSignature
    {
        $rapport = new RapportSuiviSignature();

        // sans parapheur, aucune décision n'a pu partir en signature
        if (!$this->parapheur->estDisponible()) {
            return $rapport;
        }

        foreach ($this->decisionAmenagementExamensRepository->enCoursDeSignature($limite) as $decision) {
            $rapport = $rapport->avec(examinees: 1)->avec(...$this->traiter($decision));
        }

        // Le passage est planifié : sans décision à suivre, on ne remplit pas les journaux.
        if (0 === $rapport->examinees) {
            $this->logger->debug('Suivi des signatures : aucune décision en cours de signature.');

            return $rapport;
        }

        // tout le lot en échec : le parapheur est probablement injoignable
        if ($rapport->erreurs === $rapport->examinees) {
            $this->logger->critical(
                'Suivi des signatures : aucune des {examinees} décision(s) n\'a pu être vérifiée, '
                . 'le parapheur semble injoignable.',
                $rapport->toArray(),
            );

            return $rapport;
        }

        $this->logger->info('Suivi des signatures : {examinees} décision(s) examinée(s).', $rapport->toArray());

        return $rapport;
    }

    /**
     * Interroge le parapheur pour une seule décision en signature, à la demande du gestionnaire.
     *
     * @return bool faux si le parapheur n'a pas pu être interrogé ou le document signé récupéré
     */
    public function verifier(DecisionAmenagementExamens $decision): bool
    {
        if (!$this->parapheur->estDisponible() || null === $decision->getIdDocumentParapheur()) {
            return false;
        }

        return !array_key_exists('erreurs', $this->traiter($decision));
    }

    /**
     * @return array<string, int> incréments à reporter au bilan
     */
    private function traiter(DecisionAmenagementExamens $decision): array
    {
        try {
            $suivi = $this->parapheur->suivre($decision->getIdDocumentParapheur());
        } catch (DocumentInconnuException) {
            // le parapheur ne connaît plus le document : le réinterroger ne servirait à rien
            $decision->setEtat(DecisionAmenagementExamens::ETAT_REFUSEE);
            $decision->setEtatSignature(DecisionAmenagementExamens::ETAT_SIGNATURE_ERREUR);
            $this->decisionAmenagementExamensRepository->save($decision, true);
            $this->logger->error(
                'Décision {id} : document {document} inconnu du parapheur, suivi arrêté.',
                ['id' => $decision->getId(), 'document' => $decision->getIdDocumentParapheur()],
            );

            return ['closes' => 1];
        } catch (Throwable $e) {
            // n'interrompt pas le lot : la décision est reprise au passage suivant
            $this->logger->error(
                'Suivi de signature impossible pour la décision {id} : {erreur}',
                ['id' => $decision->getId(), 'erreur' => $e->getMessage()],
            );

            return ['erreurs' => 1];
        }

        $decision->setDerniereVerificationSignature($this->now());

        if (DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE === $suivi->etat) {
            $this->decisionAmenagementExamensRepository->save($decision, true);

            return [];
        }

        if (DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE === $suivi->etat) {
            return $this->finaliser($decision, $suivi);
        }

        // rendue sans signature : le document rejoint aussi le dossier, pour l'historique
        if (null === $this->archiverDocument($decision, $suivi->etat)) {
            return ['erreurs' => 1];
        }

        $decision->setEtat(DecisionAmenagementExamens::ETAT_REFUSEE);
        $decision->setEtatSignature($suivi->etat);
        $this->decisionAmenagementExamensRepository->save($decision, true);
        $this->logger->warning(
            'Décision {id} : circuit de signature terminé sans signature ({etat}).',
            ['id' => $decision->getId(), 'etat' => $suivi->etat],
        );

        return match ($suivi->etat) {
            DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE => ['refusees' => 1],
            default => ['closes' => 1],
        };
    }

    /**
     * Récupère le PDF signé, le dépose au dossier, clôt la décision et l'envoie à l'étudiant si le
     * parapheur ne le fait pas.
     *
     * @return array<string, int>
     */
    private function finaliser(DecisionAmenagementExamens $decision, SuiviSignature $suivi): array
    {
        $pdf = $this->archiverDocument($decision, DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE);
        if (null === $pdf) {
            return ['erreurs' => 1];
        }

        if (null === $suivi->dateSignature) {
            // on n'invente pas de date de signature
            $this->logger->warning(
                'Décision {id} signée sans date de signature fournie par le parapheur.',
                ['id' => $decision->getId()],
            );
        }

        $decision->setDateSignature($suivi->dateSignature);
        $decision->setEtatSignature(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE);
        $decision->setEtat(DecisionAmenagementExamens::ETAT_EDITE);
        $this->decisionAmenagementExamensRepository->save($decision, true);

        if (!$this->parapheurEnvoieDocument) {
            try {
                $this->mailService->envoyerDecision($decision, $pdf);
            } catch (Throwable $e) {
                // la décision est signée et au dossier : l'e-mail manqué se transmet à la main, le lot continue
                $this->logger->error(
                    'Décision {id} signée et déposée au dossier, mais e-mail au bénéficiaire impossible : {erreur}',
                    ['id' => $decision->getId(), 'erreur' => $e->getMessage()],
                );
            }
        }

        return ['signees' => 1];
    }

    /**
     * Dépose au dossier du bénéficiaire le document rendu par le parapheur.
     *
     * @return ?string le PDF déposé ; null s'il n'a pas pu être récupéré ou déposé : la décision est reprise au
     *                 passage suivant
     */
    private function archiverDocument(DecisionAmenagementExamens $decision, string $etatSignature): ?string
    {
        // sans demandeur mémorisé au dépôt, la pièce jointe n'a pas d'auteur
        $uidDemandeur = $decision->getUidDemandeurSignature();
        if (null === $uidDemandeur) {
            $this->logger->error(
                'Décision {id} rendue par le parapheur ({etat}) mais sans demandeur mémorisé : archivage impossible.',
                ['id' => $decision->getId(), 'etat' => $etatSignature],
            );
            $this->decisionAmenagementExamensRepository->save($decision, true);

            return null;
        }

        try {
            $pdf = $this->parapheur->telecharger($decision->getIdDocumentParapheur());
            $this->archivageDecision->archiver(
                decision: $decision,
                pdf: $pdf,
                auteur: $this->utilisateurManager->parUid($uidDemandeur),
                etatSignature: $etatSignature,
            );
        } catch (Throwable $e) {
            $this->logger->error(
                'Décision {id} rendue par le parapheur ({etat}) mais récupération du PDF impossible : {erreur}',
                ['id' => $decision->getId(), 'etat' => $etatSignature, 'erreur' => $e->getMessage()],
            );
            $this->decisionAmenagementExamensRepository->save($decision, true);

            return null;
        }

        return $pdf;
    }
}
