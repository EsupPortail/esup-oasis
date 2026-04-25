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
use ApiPlatform\OpenApi\Model\Operation;
use App\Filter\CaseInsensitiveOrderFilter;
use App\Filter\PreloadAssociationsFilter;
use App\State\Composante\ComposanteProvider;
use App\State\Composante\PatchComposanteProcessor;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['id']),
        new GetCollection(uriTemplate: self::COLLECTION_URI, map: false),
        new Patch(uriTemplate: self::ITEM_URI, uriVariables: ['id'], security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Referentiel']),
    provider: ComposanteProvider::class,
    processor: PatchComposanteProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\Composante::class),
)]
#[ApiFilter(CaseInsensitiveOrderFilter::class, properties: ['libelle'])]
#[ApiFilter(PreloadAssociationsFilter::class)]
final class Composante
{
    public const string COLLECTION_URI = '/composantes';
    public const string ITEM_URI = self::COLLECTION_URI . '/{id}';
    public const string GROUP_IN = 'composante:in';
    public const string GROUP_OUT = 'composante:out';

    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_OUT])]
    public int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id;
        }
    }

    #[Groups([Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT, Amenagement::GROUP_OUT, self::GROUP_OUT])]
    public string $libelle {
        get {
            $prop = new ReflectionProperty(self::class, 'libelle');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelle = $this->entity->getLibelle() ?? '';
            }
            return $this->libelle;
        }
    }

    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public array $referents {
        get {
            $prop = new ReflectionProperty(self::class, 'referents');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->referents = array_map(fn($u) => new Utilisateur($u), $this->entity->getReferents()->toArray());
            }
            return $this->referents ?? [];
        }
    }

    public function __construct(
        private readonly ?\App\Entity\Composante $entity = null,
    ) {}
}
