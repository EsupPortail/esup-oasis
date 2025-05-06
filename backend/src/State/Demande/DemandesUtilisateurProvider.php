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

namespace App\State\Demande;

use ApiPlatform\Metadata\Operation;
use App\ApiResource\Utilisateur;
use Override;

class DemandesUtilisateurProvider extends DemandeProvider
{
    #[Override] public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $context['filters']['demandeur'] = Utilisateur::COLLECTION_URI . '/' . $uriVariables['uid'];

        unset($uriVariables['uid']);

        return parent::provide($operation, $uriVariables, $context);
    }
}