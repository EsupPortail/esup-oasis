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

namespace App\State\Utilisateur;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Filter\RenfortFilter;

readonly class RenfortProvider implements ProviderInterface
{

    public function __construct(private UtilisateurProvider $utilisateurProvider)
    {

    }

    /**
     * @param Operation $operation
     * @param array     $uriVariables
     * @param array     $context
     * @return object|array|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /**
         * Rappel du vilain hack : le filtre custom ajoute un filtre sur le getcollection() d'utilisateur,
         * on l'ajoute à la main sans que le client ne le voie.
         */
        $context['filters'][RenfortFilter::PROPERTY] = true;

        return $this->utilisateurProvider->provide($operation, $uriVariables, $context);
    }

}