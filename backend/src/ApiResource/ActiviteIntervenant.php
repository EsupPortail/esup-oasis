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

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Filter\ActivitePeriodeFilter;
use App\Filter\NestedUtilisateurFilter;
use App\State\Evenement\ActiviteIntervenantProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/suivis/intervenants',
            openapi: new Operation(tags: ['Suivis']),
            paginationEnabled: false
        ),
        new Get(
            uriTemplate: '/suivis/intervenants/{id}',
            uriVariables: ['id'],
            status: 405,
            openapi: false,
            provider: [self::class, 'methodNotAllowed'],
        ),
    ],
    normalizationContext: ['groups' => self::OUT],
    security: 'is_granted("ROLE_GESTIONNAIRE")',
    provider: ActiviteIntervenantProvider::class,
    stateOptions: new Options(
        entityClass: \App\Entity\Evenement::class
    )
)]
#[ApiFilter(SearchFilter::class, properties: [
    'type' => 'exact',
    'campus' => 'exact',
])]
#[ApiFilter(NestedUtilisateurFilter::class, properties: ['intervenant' => 'intervenant.utilisateur'])]
#[ApiFilter(DateFilter::class, properties: ['debut', 'fin'])]
#[ApiFilter(ExistsFilter::class, properties: ['intervenant', 'dateAnnulation'])]
#[ApiFilter(ActivitePeriodeFilter::class)]
class ActiviteIntervenant extends ActiviteUtilisateur
{
    public const string OUT = 'ActiviteIntervenant:out';
}