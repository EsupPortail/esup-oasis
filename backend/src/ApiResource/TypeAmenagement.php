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
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: self::COLLECTION_URI),
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['id' => 'id']),
        new Post(uriTemplate: self::COLLECTION_URI, security: "is_granted('ROLE_ADMIN')", map: false),
        new Patch(uriTemplate: self::ITEM_URI, security: "is_granted('ROLE_ADMIN')", map: false),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Referentiel']),
    processor: TypeAmenagementProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\TypeAmenagement::class),
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
                $this->libelle = $this->entity->getLibelle();
            }
            return $this->libelle;
        }
    }

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public ?string $libelleLong {
        get {
            $prop = new ReflectionProperty(self::class, 'libelleLong');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelleLong = $this->entity->getLibelleLong();
            }
            return $this->libelleLong ?? null;
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

    #[Groups([self::GROUP_IN, self::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    #[Assert\NotNull]
    public CategorieAmenagement $categorie {
        get {
            $prop = new ReflectionProperty(self::class, 'categorie');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getCategorie()) {
                $this->categorie = new CategorieAmenagement($this->entity->getCategorie());
            }
            return $this->categorie;
        }
    }

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public bool $pedagogique {
        get {
            $prop = new ReflectionProperty(self::class, 'pedagogique');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->pedagogique = $this->entity->isPedagogique();
            }
            return $this->pedagogique ?? false;
        }
    }

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public bool $examens {
        get {
            $prop = new ReflectionProperty(self::class, 'examens');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->examens = $this->entity->isExamens();
            }
            return $this->examens ?? false;
        }
    }

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public bool $aideHumaine {
        get {
            $prop = new ReflectionProperty(self::class, 'aideHumaine');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->aideHumaine = $this->entity->isAideHumaine();
            }
            return $this->aideHumaine ?? false;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\TypeAmenagement $entity = null,
    ) {}
}
