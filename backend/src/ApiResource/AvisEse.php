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

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\AvisEse\AvisEseDeleteProcessor;
use App\State\AvisEse\AvisEsePatchProcessor;
use App\State\AvisEse\AvisEsePostProcessor;
use App\State\AvisEse\AvisEseProvider;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI,
            uriVariables: [
                'uid' => new Link(fromProperty: 'uid', toProperty: 'utilisateur', fromClass: Utilisateur::class),
            ],
            map: false,
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            uriVariables: [
                'uid' => new Link(fromProperty: 'uid', toProperty: 'utilisateur', fromClass: Utilisateur::class),
            ],
            security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
            read: false,
            processor: AvisEsePostProcessor::class,
            map: false,
        ),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: [
                'uid' => new Link(fromProperty: 'uid', toProperty: 'utilisateur', fromClass: Utilisateur::class),
                'id',
            ],
            map: false,
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: [
                'uid' => new Link(fromProperty: 'uid', toProperty: 'utilisateur', fromClass: Utilisateur::class),
                'id',
            ],
            security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
            read: true,
            processor: AvisEsePatchProcessor::class,
        ),
        new Delete(
            uriTemplate: self::ITEM_URI,
            uriVariables: [
                'uid' => new Link(fromProperty: 'uid', toProperty: 'utilisateur', fromClass: Utilisateur::class),
                'id',
            ],
            security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
            read: true,
            processor: AvisEseDeleteProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    provider: AvisEseProvider::class,
    stateOptions: new Options(entityClass: \App\Entity\AvisEse::class),
)]
#[ApiFilter(OrderFilter::class, properties: ['debut'])]
class AvisEse
{
    public const string COLLECTION_URI = Utilisateur::ITEM_URI . '/avis_ese';
    public const string ITEM_URI = self::COLLECTION_URI . '/{id}';
    public const string GROUP_IN = 'avis_ese:in';
    public const string GROUP_OUT = 'avis_ese:out';

    public ?Utilisateur $utilisateur = null {
        get {
            if ($this->utilisateur === null && $this->entity !== null && $this->entity->getUtilisateur() !== null) {
                $this->utilisateur = new Utilisateur($this->entity->getUtilisateur());
            }
            return $this->utilisateur ?? null;
        }
    }

    #[Groups([self::GROUP_OUT])]
    #[ApiProperty(identifier: true)]
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
    public ?string $commentaire {
        get {
            $prop = new ReflectionProperty(self::class, 'commentaire');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->commentaire = $this->entity->getCommentaire();
            }
            return $this->commentaire ?? null;
        }
    }

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public DateTimeInterface $debut {
        get {
            $prop = new ReflectionProperty(self::class, 'debut');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->debut = $this->entity->getDebut();
            }
            return $this->debut;
        }
    }

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public ?DateTimeInterface $fin {
        get {
            $prop = new ReflectionProperty(self::class, 'fin');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->fin = $this->entity->getFin();
            }
            return $this->fin ?? null;
        }
    }
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public ?Telechargement $fichier {
        get {
            $prop = new ReflectionProperty(self::class, 'fichier');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getFichier() !== null) {
                $this->fichier = new Telechargement($this->entity->getFichier());
            }
            return $this->fichier ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\AvisEse $entity = null,
    ) {}
}
