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

use App\Message\DemandeRefuseeMessage;
use App\Service\MailService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: DemandeRefuseeMessage::class)]
readonly class DemandeRefuseeMessageHandler
{
    public function __construct(private MailService $mailService)
    {

    }

    public function __invoke(DemandeRefuseeMessage $message): void
    {
        //Envoi d'un mail "désolé, refusé"
        $this->mailService->envoyerNotificationRefusDemande($message->getDemandeur(),
            $message->getTypeDemande(), $message->getCommentaire());
    }

}