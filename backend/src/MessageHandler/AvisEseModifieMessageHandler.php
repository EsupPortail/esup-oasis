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

namespace App\MessageHandler;

use App\Message\AvisEseModifieMessage;
use App\Repository\UtilisateurRepository;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: AvisEseModifieMessage::class)]
class AvisEseModifieMessageHandler
{
    use ClockAwareTrait;

    public function __construct(private readonly DecisionAmenagementManager $decisionAmenagementManager,
                                private readonly UtilisateurRepository      $utilisateurRepository)
    {

    }

    public function __invoke(AvisEseModifieMessage $message): void
    {
        /**
         * On doit recalculer l'état de décision d'aménagement pour la période correspondante
         * La période est toujours une année U!
         */
        $bornesAnneeConcernee = $message->getBornesAnnee();
        $beneficiaire = $message->getBeneficiaire();

        $this->decisionAmenagementManager->majEtatDecision($beneficiaire, $bornesAnneeConcernee['debut'], $bornesAnneeConcernee['fin']);

        /**
         * On doit recalculer le champ etatAvisEse de l'utilisateur
         */
        $beneficiaire->setEtatAvisEse($beneficiaire->getEtatAvisEseCalcule());
        $this->utilisateurRepository->save($beneficiaire, true);
    }

}