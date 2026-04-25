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
use App\State\Inscription\InscriptionProvider;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Get(
        uriTemplate: self::ITEM_URI,
        uriVariables: ['id'],
        openapi: false,
        provider: InscriptionProvider::class,
        stateOptions: new Options(entityClass: \App\Entity\Inscription::class),
    ),
])]
final class Inscription
{
    public const string COLLECTION_URI = '/inscriptions';
    public const string ITEM_URI = self::COLLECTION_URI . '/{id}';

    #[ApiProperty(identifier: true)]
    public int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id;
        }
    }

    #[Groups([
        Utilisateur::GROUP_OUT,
        Demande::GROUP_OUT,
        Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT,
        Amenagement::GROUP_OUT,
    ])]
    public Formation $formation {
        get {
            $prop = new ReflectionProperty(self::class, 'formation');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getFormation()) {
                $this->formation = new Formation($this->entity->getFormation());
            }
            return $this->formation;
        }
    }

    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public DateTimeInterface $debut {
        get {
            $prop = new ReflectionProperty(self::class, 'debut');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->debut = $this->entity->getDebut();
            }
            return $this->debut;
        }
    }
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public DateTimeInterface $fin {
        get {
            $prop = new ReflectionProperty(self::class, 'fin');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->fin = $this->entity->getFin();
            }
            return $this->fin;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\Inscription $entity = null,
    ) {}
}
