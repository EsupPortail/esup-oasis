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

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Filter\CaseInsensitiveOrderFilter;
use App\State\TypeSuiviAmenagement\TypeSuiviAmenagementProcessor;
use App\State\TypeSuiviAmenagement\TypeSuiviAmenagementProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI
        ),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            security: "is_granted('ROLE_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Referentiel']),
    provider: TypeSuiviAmenagementProvider::class,
    processor: TypeSuiviAmenagementProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\TypeSuiviAmenagement::class)
)]
#[ApiFilter(CaseInsensitiveOrderFilter::class, properties: ['libelle'])]
class TypeSuiviAmenagement
{
    public const string COLLECTION_URI = '/types_suivi_amenagements';
    public const string ITEM_URI = '/types_suivi_amenagements/{id}';
    public const string GROUP_IN = 'type_suivi_amenagement:in';
    public const string GROUP_OUT = 'type_suivi_amenagement:out';

    #[Groups([self::GROUP_OUT])]
    public ?int $id = null;
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    public string $libelle;
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public bool $actif = true;

}