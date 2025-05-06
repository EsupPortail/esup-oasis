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

use App\Message\RessourceCollectionModifieeMessage;
use App\Service\HttpCacheInvalidator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class RessourceCollectionModifieeMessageHandler
{
    public function __construct(private HttpCacheInvalidator $httpCacheInvalidator)
    {
    }

    public function __invoke(RessourceCollectionModifieeMessage $message): void
    {
        $this->httpCacheInvalidator->invalidateCollectionForRessource($message->getResource());
    }
}