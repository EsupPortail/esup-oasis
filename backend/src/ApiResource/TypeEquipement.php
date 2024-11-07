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
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Filter\CaseInsensitiveOrderFilter;
use App\State\TypeEquipement\TypeEquipementProcessor;
use App\State\TypeEquipement\TypeEquipementProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations            : [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI,
            openapi    : new Operation(
                tags       : ['Referentiel'],
                summary    : "Liste des types d'équipements",
                description: "Retourne la liste des types d'équipements",
            )
        ),
        new Get(
            uriTemplate : self::ITEM_URI,
            uriVariables: ['id' => 'id'],
            openapi     : new Operation(
                tags       : ['Referentiel'],
                summary    : "Détail d'un types d'équipements",
                description: "Retourne le détail du type d'équipement demandé"
            )
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
    provider              : TypeEquipementProvider::class,
    processor             : TypeEquipementProcessor::class,
    stateOptions          : new Options(entityClass: \App\Entity\TypeEquipement::class)
)]
#[ApiFilter(CaseInsensitiveOrderFilter::class, properties: ['libelle'])]
final class TypeEquipement
{
    public const string COLLECTION_URI = '/types_equipements';
    public const string ITEM_URI = '/types_equipements/{id}';
    public const string GROUP_IN = 'type_equipement:in';
    public const string GROUP_OUT = 'type_equipement:out';

    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_OUT])]
    public int $id;
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    #[Assert\NotBlank]
    public string $libelle;
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public bool $actif = true;
}