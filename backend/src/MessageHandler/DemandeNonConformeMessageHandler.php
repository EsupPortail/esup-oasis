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

use App\Message\DemandeNonConformeMessage;
use App\Service\MailService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: DemandeNonConformeMessage::class)]
readonly class DemandeNonConformeMessageHandler
{
    public function __construct(private MailService $mailService)
    {

    }


    public function __invoke(DemandeNonConformeMessage $message): void
    {
        //Envoi d'un mail "Désolé, réessayez"
        $this->mailService->envoyerMessageDemandeNonConforme($message->getDemandeur(), $message->getTypeDemande(), $message->getCommentaire());
    }
}