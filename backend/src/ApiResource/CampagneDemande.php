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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\CampagneDemande\CampagneDemandeProcessor;
use App\State\CampagneDemande\CampagneDemandeProvider;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ApiResource(
    operations: [
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['id', 'typeId'], map: false),
        new GetCollection(uriTemplate: self::COLLECTION_URI, uriVariables: ['typeId'], map: false),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            uriVariables: ['typeId'],
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            map: false,
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id', 'typeId'],
            security: "is_granted('ROLE_ADMIN')",
            map: false,
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Demandes']),
    provider: CampagneDemandeProvider::class,
    processor: CampagneDemandeProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\CampagneDemande::class),
)]
class CampagneDemande
{
    public const string COLLECTION_URI = '/types_demandes/{typeId}/campagnes';
    public const string ITEM_URI = '/types_demandes/{typeId}/campagnes/{id}';
    public const string GROUP_IN = 'campagne:in';
    public const string GROUP_OUT = 'campagne:out';

    #[Ignore]
    public ?int $typeId {
        get {
            $prop = new ReflectionProperty(self::class, 'typeId');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->typeId = $this->entity->getTypeDemande()->getId();
            }
            return $this->typeId;
        }
    }

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
    public DateTimeInterface $debut {
        get {
            $prop = new ReflectionProperty(self::class, 'debut');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->debut = $this->entity->getDebut();
            }
            return $this->debut;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public DateTimeInterface $fin {
        get {
            $prop = new ReflectionProperty(self::class, 'fin');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->fin = $this->entity->getFin();
            }
            return $this->fin;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?DateTimeInterface $dateCommission {
        get {
            $prop = new ReflectionProperty(self::class, 'dateCommission');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->dateCommission = $this->entity->getDateCommission();
            }
            return $this->dateCommission ?? null;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?DateTimeInterface $dateArchivage {
        get {
            $prop = new ReflectionProperty(self::class, 'dateArchivage');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->dateArchivage = $this->entity->getDateArchivage();
            }
            return $this->dateArchivage ?? null;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?Commission $commission {
        get {
            $prop = new ReflectionProperty(self::class, 'commission');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getCommission() !== null) {
                $this->commission = new Commission($this->entity->getCommission());
            }
            return $this->commission ?? null;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?int $anneeCible {
        get {
            $prop = new ReflectionProperty(self::class, 'anneeCible');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->anneeCible = $this->entity->getAnneeCible();
            }
            return $this->anneeCible ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\CampagneDemande $entity = null,
    ) {}
}
