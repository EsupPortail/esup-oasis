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
use App\State\TypologieHandicap\TypologieProcessor;
use App\State\TypologieHandicap\TypologieProvider;
use ReflectionProperty;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: self::COLLECTION_URI),
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['id']),
        new Patch(uriTemplate: self::ITEM_URI, uriVariables: ['id']),
        new Post(uriTemplate: self::COLLECTION_URI),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Referentiel']),
    security: 'is_granted("' . BeneficiaireProfil::VOIR_PROFILS . '")',
    stateOptions: new Options(entityClass: \App\Entity\TypologieHandicap::class),
)]
#[ApiFilter(CaseInsensitiveOrderFilter::class, properties: ['libelle'])]
#[Map(target: \App\Entity\TypologieHandicap::class)]
class TypologieHandicap
{
    public const string COLLECTION_URI = '/typologies';
    public const string ITEM_URI = '/typologies/{id}';
    public const string GROUP_IN = 'typologies:in';
    public const string GROUP_OUT = 'typologies:out';

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

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public string $libelle {
        get {
            $prop = new ReflectionProperty(self::class, 'libelle');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelle = $this->entity->getLibelle();
            }
            return $this->libelle;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
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
        private readonly ?\App\Entity\TypologieHandicap $entity = null,
    ) {}
}
