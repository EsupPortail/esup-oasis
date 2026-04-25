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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\Entretien\EntretienProcessor;
use App\State\Entretien\EntretienProvider;
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
            security: "is_granted('ROLE_GESTIONNAIRE') or request.attributes.get('uid') == user.getUid()",
            map: false,
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            uriVariables: [
                'uid' => new Link(fromProperty: 'uid', toProperty: 'utilisateur', fromClass: Utilisateur::class),
            ],
            security: "is_granted('ROLE_GESTIONNAIRE')",
            read: false,
            map: false,
        ),
        new Get(uriTemplate: self::ITEM_URI, uriVariables: [
            'uid' => new Link(fromProperty: 'uid', toProperty: 'utilisateur', fromClass: Utilisateur::class),
            'id',
        ]),
        new Patch(uriTemplate: self::ITEM_URI, uriVariables: [
            'uid' => new Link(fromProperty: 'uid', toProperty: 'utilisateur', fromClass: Utilisateur::class),
            'id',
        ]),
        new Delete(uriTemplate: self::ITEM_URI, uriVariables: [
            'uid' => new Link(fromProperty: 'uid', toProperty: 'utilisateur', fromClass: Utilisateur::class),
            'id',
        ]),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    provider: EntretienProvider::class,
    processor: EntretienProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\Entretien::class),
)]
#[ApiFilter(OrderFilter::class, properties: ['date'])]
class Entretien
{
    public const string COLLECTION_URI = Utilisateur::ITEM_URI . '/entretiens';
    public const string ITEM_URI = self::COLLECTION_URI . '/{id}';
    public const string GROUP_IN = 'entretien:in';
    public const string GROUP_OUT = 'entretien:out';

    public Utilisateur $utilisateur {
        get {
            $prop = new ReflectionProperty(self::class, 'utilisateur');
            if (!$prop->isInitialized($this) && $this->entity !== null && null !== $this->entity->getUtilisateur()) {
                $this->utilisateur = new Utilisateur($this->entity->getUtilisateur());
            }
            return $this->utilisateur;
        }
    }

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
    public DateTimeInterface $date {
        get {
            $prop = new ReflectionProperty(self::class, 'date');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->date = $this->entity->getDate();
            }
            return $this->date;
        }
    }

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public ?Telechargement $fichier {
        get {
            $prop = new ReflectionProperty(self::class, 'fichier');
            if (!$prop->isInitialized($this) && $this->entity !== null && null !== $this->entity->getFichier()) {
                $this->fichier = new Telechargement($this->entity->getFichier());
            }
            return $this->fichier ?? null;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public Utilisateur $gestionnaire {
        get {
            $prop = new ReflectionProperty(self::class, 'gestionnaire');
            if (!$prop->isInitialized($this) && $this->entity !== null && null !== $this->entity->getGestionnaire()) {
                $this->gestionnaire = new Utilisateur($this->entity->getGestionnaire());
            }
            return $this->gestionnaire;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\Entretien $entity = null,
    ) {}
}
