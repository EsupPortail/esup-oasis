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

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Filter\CaseInsensitiveOrderFilter;
use ReflectionProperty;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: self::COLLECTION_URI),
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['identifiantExterne']),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            denormalizationContext: ['groups' => [self::GROUP_POST]],
            read: false,
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['identifiantExterne'],
            denormalizationContext: ['groups' => [self::GROUP_PATCH]],
        ),
        new Delete(uriTemplate: self::ITEM_URI, uriVariables: ['identifiantExterne']),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    openapi: new Operation(tags: ['Referentiel']),
    security: "is_granted('ROLE_ADMIN')",
    stateOptions: new Options(entityClass: \App\Entity\SportifHautNiveau::class),
)]
#[ApiFilter(CaseInsensitiveOrderFilter::class, properties: ['nom'])]
#[ApiFilter(SearchFilter::class, properties: [
    'nom' => 'ipartial',
    'prenom' => 'ipartial',
    'identifiantExterne' => 'ipartial',
])]
#[Map(target: \App\Entity\SportifHautNiveau::class)]
class SportifHautNiveau
{
    public const string COLLECTION_URI = '/sportifs_haut_niveau';
    public const string ITEM_URI = '/sportifs_haut_niveau/{identifiantExterne}';

    public const string GROUP_OUT = 'sportif_haut_niveau:out';
    public const string GROUP_POST = 'sportif_haut_niveau:post';
    public const string GROUP_PATCH = 'sportif_haut_niveau:patch';

    #[Ignore]
    public ?int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }

    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_OUT, self::GROUP_POST])]
    public string $identifiantExterne {
        get {
            $prop = new ReflectionProperty(self::class, 'identifiantExterne');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->identifiantExterne = $this->entity->getIdentifiantExterne();
            }
            return $this->identifiantExterne;
        }
    }

    #[Groups([self::GROUP_PATCH, self::GROUP_POST, self::GROUP_OUT])]
    public ?string $nom {
        get {
            $prop = new ReflectionProperty(self::class, 'nom');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->nom = $this->entity->getNom();
            }
            return $this->nom ?? null;
        }
    }
    #[Groups([self::GROUP_PATCH, self::GROUP_POST, self::GROUP_OUT])]
    public ?string $prenom {
        get {
            $prop = new ReflectionProperty(self::class, 'prenom');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->prenom = $this->entity->getPrenom();
            }
            return $this->prenom ?? null;
        }
    }
    #[Groups([self::GROUP_PATCH, self::GROUP_POST, self::GROUP_OUT])]
    public ?int $anneeNaissance {
        get {
            $prop = new ReflectionProperty(self::class, 'anneeNaissance');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->anneeNaissance = $this->entity->getAnneeNaissance();
            }
            return $this->anneeNaissance ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\SportifHautNiveau $entity = null,
    ) {}
}
