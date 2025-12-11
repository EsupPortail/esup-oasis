<?php

/*
 * Copyright (c) 2024. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\State\DecisionAmenagementExamens;

use App\Entity\AvisEse;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\Utilisateur;
use App\Message\ModificationUtilisateurMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\DecisionAmenagementExamensRepository;
use App\State\TransformerService;
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

    public function __construct(private readonly DecisionAmenagementExamensRepository $decisionAmenagementExamensRepository,
                                private readonly UtilisateurManager                   $utilisateurManager,
                                private readonly MessageBusInterface                  $messageBus, private readonly TransformerService $transformerService)
    {

    }

    public function parUidEtAnnee(string $uid, int $annee): DecisionAmenagementExamens|null
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
    public function majEtatDecision(Utilisateur $beneficiaire, DateTimeInterface $debutPeriode, DateTimeInterface $finPeriode): void
    {
        /**
         * Appelé si:
         * - avis ESE mis à jour
         * - aménagement d'examen mis à jour
         */

        $decision = $beneficiaire->getDecisionAmenagementExamens($debutPeriode, $finPeriode);

        $now = $this->now();
        $dateConsideree = match (true) {
            ($now >= $debutPeriode && $now <= $finPeriode) => $now,
            default => $debutPeriode
        };

        $amenagementsEnCours = array_filter($beneficiaire->getAmenagementsActifs(), fn($amenagement) => $amenagement->getType()->isExamens());


        //si pas d'avis ESE en cours ni d'aménagement d'examens en cours pour la période, pas de décision!
        if ($beneficiaire->getEtatAvisEse($dateConsideree) !== AvisEse::ETAT_EN_COURS && count($amenagementsEnCours) === 0) {
            // décision existante, si supprimable on supprime
            if (null !== $decision && $decision->getEtat() !== DecisionAmenagementExamens::ETAT_EDITE) {
                $this->decisionAmenagementExamensRepository->remove($decision, true);
            }
            return;
        }

        //si avis ESE ou aménagement examens en cours, on veut pouvoir valider/revalider pour édition!

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

        $this->messageBus->dispatch(new ModificationUtilisateurMessage($beneficiaire));
        //ici on veut aussi rafraichir le cache de l'utilisateur et de la decision elle-même
        $decisionResource = $this->transformerService->transform($decision, \App\ApiResource\DecisionAmenagementExamens::class);
        $this->messageBus->dispatch(new RessourceModifieeMessage($decisionResource));
        $utilisateurResource = $this->transformerService->transform($beneficiaire, \App\ApiResource\Utilisateur::class);
        $this->messageBus->dispatch(new RessourceModifieeMessage($utilisateurResource));

    }

    public function getDecisionCourante(Utilisateur $utilisateur): ?DecisionAmenagementExamens
    {
        $bornes = $this->bornesAnneeDuJour();
        return $utilisateur->getDecisionAmenagementExamens($bornes['debut'], $bornes['fin']);
    }

}