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

use App\Message\RessourceModifieeMessage;
use App\Service\HttpCacheInvalidator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RessourceModifieeMessageHandler
{
    public function __construct(private readonly HttpCacheInvalidator $httpCacheInvalidator)
    {
    }

    public function __invoke(RessourceModifieeMessage $message): void
    {
        $this->httpCacheInvalidator->invalidateRessource($message->getResource());
    }

}