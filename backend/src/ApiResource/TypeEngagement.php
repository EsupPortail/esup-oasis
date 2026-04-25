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
use App\State\TypeEngagement\TypeEngagementProcessor;
use App\State\TypeEngagement\TypeEngagementProvider;
use ReflectionProperty;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI,
            openapi: new Operation(
                tags: ['Referentiel'],
                summary: "Liste des types d'engagements",
                description: "Retourne la liste des types d'équipements",
            ),
        ),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id' => 'id'],
            openapi: new Operation(
                tags: ['Referentiel'],
                summary: "Détail d'un types d'engagements",
                description: "Retourne le détail du type d'équipement demandé",
            ),
        ),
        new Post(uriTemplate: self::COLLECTION_URI, security: "is_granted('ROLE_ADMIN')"),
        new Patch(uriTemplate: self::ITEM_URI, security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Referentiel']),
    stateOptions: new Options(entityClass: \App\Entity\TypeEngagement::class),
)]
#[ApiFilter(CaseInsensitiveOrderFilter::class, properties: ['libelle'])]
#[Map(target: \App\Entity\TypeEngagement::class)]
final class TypeEngagement
{
    public const string COLLECTION_URI = '/types_engagements';
    public const string ITEM_URI = '/types_engagements/{id}';
    public const string GROUP_IN = 'types_engagements:in';
    public const string GROUP_OUT = 'types_engagements:out';

    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_OUT])]
    public ?int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    #[Assert\NotBlank]
    public string $libelle {
        get {
            $prop = new ReflectionProperty(self::class, 'libelle');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelle = $this->entity->getLibelle() ?? '';
            }
            return $this->libelle;
        }
    }
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public bool $actif {
        get {
            $prop = new ReflectionProperty(self::class, 'actif');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->actif = $this->entity->isActif();
            }
            return $this->actif ?? true;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\TypeEngagement $entity = null,
    ) {}
}
