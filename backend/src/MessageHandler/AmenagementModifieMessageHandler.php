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

use App\Message\AmenagementModifieMessage;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: AmenagementModifieMessage::class)]
readonly class AmenagementModifieMessageHandler
{
    public function __construct(private DecisionAmenagementManager $decisionAmenagementManager,
                                private LoggerInterface            $logger)
    {

    }

    public function __invoke(AmenagementModifieMessage $message): void
    {
        $bornesAnneeConcernee = $message->getBornesAnnee();
        $this->logger->info('Aménagement modifié pour  : ' . $message->getBeneficiaire()->getUid() . ', année : ' . json_encode($bornesAnneeConcernee));
        $beneficiaire = $message->getBeneficiaire();

        if ($message->isExamens()) {
            $this->decisionAmenagementManager->majEtatDecision($beneficiaire, $bornesAnneeConcernee['debut'], $bornesAnneeConcernee['fin']);
        }
    }

}