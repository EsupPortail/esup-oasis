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
use App\State\TypeAmenagement\TypeAmenagementProcessor;
use App\State\TypeAmenagement\TypeAmenagementProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations            : [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI
        ),
        new Get(
            uriTemplate : self::ITEM_URI,
            uriVariables: ['id' => 'id'],
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            security   : "is_granted('ROLE_ADMIN')",
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            security   : "is_granted('ROLE_ADMIN')",
        ),
    ],
    normalizationContext  : ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi               : new Operation(tags: ['Referentiel']),
    provider              : TypeAmenagementProvider::class,
    processor             : TypeAmenagementProcessor::class,
    stateOptions          : new Options(entityClass: \App\Entity\TypeAmenagement::class)
)]
#[ApiFilter(CaseInsensitiveOrderFilter::class, properties: ['libelle'])]
#[ApiFilter(BooleanFilter::class, properties: ['examens', 'pedagogique', 'aideHumaine'])]
class TypeAmenagement
{
    public const string COLLECTION_URI = '/types_amenagements';
    public const string ITEM_URI = '/types_amenagements/{id}';
    public const string GROUP_IN = 'type_amenagement:in';
    public const string GROUP_OUT = 'type_amenagement:out';

    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public ?int $id = null;

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    #[Assert\NotBlank]
    public string $libelle;

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public ?string $libelleLong = null;

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public bool $actif = true;

    #[Groups([self::GROUP_IN, self::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    #[Assert\NotNull]
    public CategorieAmenagement $categorie;

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public ?bool $pedagogique = null;

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public ?bool $examens = null;

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public ?bool $aideHumaine = null;

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public bool $decision = false;
}