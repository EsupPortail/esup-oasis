<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\State\Amenagement;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use App\ApiResource\Amenagement;
use App\Filter\AmenagementUtilisateurFilter;
use Override;

class AmenagementProvider extends AmenagementSansFiltreProvider
{
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            //ajout d'un filtre sur utilisateur!
            $context['filters'][AmenagementUtilisateurFilter::PROPERTY] = $uriVariables['uid'];
            unset($uriVariables['uid']);

            return parent::provide($operation, $uriVariables, $context);
        }

        //on reconstruit une opération qui ne va pas exploser en vol
        $relevantVariables = ['id' => $uriVariables['id']];

        $link = new Link(parameterName: 'id', fromClass: Amenagement::class, identifiers: ['id']);
        $relevantOperation = new (get_class($operation))()
            ->withClass(Amenagement::class)
            ->withStateOptions($operation->getStateOptions())
            ->withUriVariables([$link]);

        $amenagement = parent::provide(
            operation: $relevantOperation,
            uriVariables: $relevantVariables,
            context: $context,
        );
        assert($amenagement instanceof Amenagement || null === $amenagement);

        if ($amenagement?->uid !== $uriVariables['uid']) {
            return null;
        }

        return $amenagement;
    }
}
