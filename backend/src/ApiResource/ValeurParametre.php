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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\Parametre\ValeurParametreProcessor;
use App\State\Parametre\ValeurParametreProvider;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['cle', 'id']),
        new Post(uriTemplate: self::COLLECTION_URI, uriVariables: ['cle'], read: false, map: false),
        new Patch(uriTemplate: self::ITEM_URI, uriVariables: ['cle', 'id'], map: false),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Referentiel']),
    order: ['debut' => 'DESC '],
    provider: ValeurParametreProvider::class,
    processor: ValeurParametreProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\ValeurParametre::class),
)]
#[Assert\Expression('this.fichier != null or this.valeur != null')]
class ValeurParametre
{
    public const string ITEM_URI = '/parametres/{cle}/valeurs/{id}';
    public const string COLLECTION_URI = '/parametres/{cle}/valeurs';
    public const string GROUP_IN = 'valeur_param:in';
    public const string GROUP_OUT = 'valeur_param:out';

    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_OUT, Parametre::GROUP_OUT])]
    public ?int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }

    //copie pour simplifier la gestion de l'IRI
    public string $cle {
        get {
            $prop = new ReflectionProperty(self::class, 'cle');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->cle = $this->entity->getParametre()->getCle();
            }
            return $this->cle;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN, Parametre::GROUP_OUT])]
    public ?string $valeur {
        get {
            $prop = new ReflectionProperty(self::class, 'valeur');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->valeur = $this->entity->getValeur();
            }
            return $this->valeur ?? null;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN, Parametre::GROUP_OUT])]
    public ?Telechargement $fichier {
        get {
            $prop = new ReflectionProperty(self::class, 'fichier');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getFichier() !== null) {
                $this->fichier = new Telechargement($this->entity->getFichier());
            }
            return $this->fichier ?? null;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN, Parametre::GROUP_OUT])]
    #[Assert\NotBlank]
    public DateTimeInterface $debut {
        get {
            $prop = new ReflectionProperty(self::class, 'debut');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->debut = $this->entity->getDebut();
            }
            return $this->debut;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN, Parametre::GROUP_OUT])]
    #[Assert\GreaterThan(propertyPath: 'debut')]
    public ?DateTimeInterface $fin {
        get {
            $prop = new ReflectionProperty(self::class, 'fin');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->fin = $this->entity->getFin();
            }
            return $this->fin ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\ValeurParametre $entity = null,
    ) {}
}
