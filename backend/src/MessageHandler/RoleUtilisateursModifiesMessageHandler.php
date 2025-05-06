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

use App\Message\RoleUtilisateursModifiesMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(handles: RoleUtilisateursModifiesMessage::class)]
class RoleUtilisateursModifiesMessageHandler
{
    public function __construct(private readonly TagAwareCacheInterface $cache)
    {

    }

    public function __invoke(RoleUtilisateursModifiesMessage $message): void
    {
        $this->cache->invalidateTags(['utilisateurs_roles_' . $message->getRole()]);
    }

}