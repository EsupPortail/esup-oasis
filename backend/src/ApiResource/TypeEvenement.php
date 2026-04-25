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
use App\State\TypeEvenement\TypeEvenementProcessor;
use App\State\TypeEvenement\TypeEvenementProvider;
use ReflectionProperty;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI,
            openapi: new Operation(
                tags: ['Referentiel'],
                summary: "Liste des types d'événements",
                description: "Retourne la liste des types d'événements",
            ),
        ),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id' => 'id'],
            openapi: new Operation(
                tags: ['Referentiel'],
                summary: "Détail d'un types d'événements",
                description: "Retourne le détail du type d'événements demandé",
            ),
        ),
        new Post(uriTemplate: self::COLLECTION_URI, security: "is_granted('ROLE_ADMIN')", map: false),
        new Patch(uriTemplate: self::ITEM_URI, security: "is_granted('ROLE_ADMIN')", map: false),
    ],
    normalizationContext: ['groups' => self::GROUP_OUT],
    denormalizationContext: ['groups' => self::GROUP_IN],
    openapi: new Operation(tags: ['Referentiel']),
    order: ['libelle' => 'ASC '],
    provider: TypeEvenementProvider::class,
    processor: TypeEvenementProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\TypeEvenement::class),
)]
#[ApiFilter(BooleanFilter::class, properties: ['forfait'])]
final class TypeEvenement
{
    public const string ITEM_URI = '/types_evenements/{id}';
    public const string COLLECTION_URI = '/types_evenements';
    public const string GROUP_OUT = 'typesEvenements:out';
    public const string GROUP_IN = 'typesEvenements:in';

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
    #[Assert\NotBlank]
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public string $libelle {
        get {
            $prop = new ReflectionProperty(self::class, 'libelle');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelle = $this->entity->getLibelle() ?? '';
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

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?string $couleur {
        get {
            $prop = new ReflectionProperty(self::class, 'couleur');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->couleur = $this->entity->getCouleur();
            }
            return $this->couleur ?? null;
        }
    }
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public bool $visibleParDefaut {
        get {
            $prop = new ReflectionProperty(self::class, 'visibleParDefaut');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->visibleParDefaut = $this->entity->isVisibleParDefaut();
            }
            return $this->visibleParDefaut ?? true;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public bool $avecValidation = false {
        get {
            if ($this->entity !== null) {
                return $this->entity->isAvecValidation() ?? false;
            }
            return $this->avecValidation;
        }
    }

    /**
     * @var TauxHoraire[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(TauxHoraire::class)])]
    public array $tauxHoraires {
        get {
            $prop = new ReflectionProperty(self::class, 'tauxHoraires');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->tauxHoraires = array_map(
                    fn($entity) => new TauxHoraire($entity),
                    $this->entity->getTauxHoraires()->toArray(),
                );
            }
            return $this->tauxHoraires ?? [];
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?TauxHoraire $tauxActif {
        get {
            $prop = new ReflectionProperty(self::class, 'tauxActif');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $actif = $this->entity->getTauxHoraireActif();
                $this->tauxActif = $actif ? new TauxHoraire($actif) : null;
            }
            return $this->tauxActif ?? null;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public bool $forfait {
        get {
            $prop = new ReflectionProperty(self::class, 'forfait');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->forfait = $this->entity->isForfait();
            }
            return $this->forfait ?? false;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\TypeEvenement $entity = null,
    ) {}
}
