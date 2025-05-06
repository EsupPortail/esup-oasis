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

use App\Message\ErreurTechniqueMessage;
use App\Service\MailService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: ErreurTechniqueMessage::class)]
readonly class ErreurTechniqueMessageHandler
{

    public function __construct(private MailService $mailService)
    {

    }

    public function __invoke(ErreurTechniqueMessage $message): void
    {
        $this->mailService->envoyerMailErreurTechnique($message);
    }

}