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

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Filter\CaseInsensitiveOrderFilter;
use App\State\CategorieAmenagement\CategorieAmenagementProcessor;
use App\State\CategorieAmenagement\CategorieAmenagementProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI
        ),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id']
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('ROLE_ADMIN')",
        ),
    ],
    denormalizationContext: ['groups' => self::GROUP_IN],
    openapi: new Operation(tags: ['Referentiel']),
    provider: CategorieAmenagementProvider::class,
    processor: CategorieAmenagementProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\CategorieAmenagement::class)
)]
#[ApiFilter(CaseInsensitiveOrderFilter::class, properties: ['libelle'])]
#[ApiFilter(BooleanFilter::class, properties: [
    'typesAmenagement.examens',
    'typesAmenagement.pedagogique',
    'typesAmenagement.aideHumaine',
]
)]
class CategorieAmenagement
{
    public const string COLLECTION_URI = '/categories_amenagements';
    public const string ITEM_URI = '/categories_amenagements/{id}';
    public const string GROUP_IN = 'categorie_amenagement:in';

    #[ApiProperty(identifier: true)]
    public ?int $id = null;
    #[Groups([self::GROUP_IN])]
    public string $libelle;
    #[Groups([self::GROUP_IN])]
    public bool $actif = true;
}