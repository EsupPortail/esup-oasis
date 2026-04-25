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
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\Charte\CharteProcessor;
use App\State\Charte\CharteProvider;
use ReflectionProperty;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Transform\MapCollection;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: self::COLLECTION_URI, map: false),
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['id']),
        new Post(uriTemplate: self::COLLECTION_URI, map: false),
        new Patch(uriTemplate: self::ITEM_URI, uriVariables: ['id']),
        new Delete(uriTemplate: self::ITEM_URI, uriVariables: ['id']),
    ],
    openapi: new Operation(tags: ['Referentiel']),
    provider: CharteProvider::class,
    processor: CharteProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\Charte::class),
)]
class Charte
{
    public const string COLLECTION_URI = '/chartes';
    public const string ITEM_URI = '/chartes/{id}';

    #[ApiProperty(identifier: true)]
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

    public string $libelle {
        get {
            $prop = new ReflectionProperty(self::class, 'libelle');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelle = $this->entity->getLibelle() ?? '';
            }
            return $this->libelle;
        }
    }

    public string $contenu {
        get {
            $prop = new ReflectionProperty(self::class, 'contenu');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->contenu = $this->entity->getContenu() ?? '';
            }
            return $this->contenu;
        }
    }

    /**
     * @var ProfilBeneficiaire[]
     */
    public array $profilsAssocies {
        get {
            $prop = new ReflectionProperty(self::class, 'profilsAssocies');
            if (
                !$prop->isInitialized($this)
                && $this->entity !== null
                && $this->entity->getProfilsAssocies() !== null
            ) {
                $this->profilsAssocies = array_map(
                    fn($p) => new ProfilBeneficiaire($p),
                    $this->entity->getProfilsAssocies()->toArray(),
                );
            }
            return $this->profilsAssocies ?? [];
        }
    }

    public function __construct(
        private readonly ?\App\Entity\Charte $entity = null,
    ) {}
}
