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
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Bilan;
use App\Filter\NestedUtilisateurFilter;
use App\State\BilanActivite\BilanActiviteProcessor;
use App\State\BilanActivite\BilanActiviteProvider;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI,
            security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
            map: false,
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
            map: false,
        ),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
        ),
        new Delete(uriTemplate: self::ITEM_URI, uriVariables: ['id'], security: 'object.getUid() == user.getUid()'),
    ],
    cacheHeaders: [
        'public' => false,
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Suivis'], description: 'Bilan activite'),
    provider: BilanActiviteProvider::class,
    processor: BilanActiviteProcessor::class,
    stateOptions: new Options(entityClass: Bilan::class),
)]
#[ApiFilter(OrderFilter::class, properties: ['dateDemande'])]
#[ApiFilter(NestedUtilisateurFilter::class, properties: ['demandeur' => 'demandeur'])]
class BilanActivite
{
    public const string COLLECTION_URI = '/suivis/activite';
    public const string ITEM_URI = '/suivis/activite/{id}';
    public const string GROUP_IN = 'bilan-activite:in';
    public const string GROUP_OUT = 'bilan-activite:out';

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

    #[Groups([self::GROUP_OUT])]
    public Utilisateur $demandeur {
        get {
            $prop = new ReflectionProperty(self::class, 'demandeur');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->demandeur = new Utilisateur($this->entity->getDemandeur());
            }
            return $this->demandeur;
        }
    }

    #[Assert\NotNull]
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

    #[Assert\NotNull]
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public DateTimeInterface $fin {
        get {
            $prop = new ReflectionProperty(self::class, 'fin');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->fin = $this->entity->getFin();
            }
            return $this->fin;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public DateTimeInterface $dateDemande {
        get {
            $prop = new ReflectionProperty(self::class, 'dateDemande');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->dateDemande = $this->entity->getDateDemande();
            }
            return $this->dateDemande;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateGeneration {
        get {
            $prop = new ReflectionProperty(self::class, 'dateGeneration');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->dateGeneration = $this->entity->getDateGeneration();
            }
            return $this->dateGeneration ?? null;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?Telechargement $fichier {
        get {
            $prop = new ReflectionProperty(self::class, 'fichier');
            if (!$prop->isInitialized($this) && $this->entity !== null && null !== $this->entity->getFichier()) {
                $this->fichier = new Telechargement($this->entity->getFichier());
            }
            return $this->fichier ?? null;
        }
    }

    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public array $gestionnaires {
        get {
            $prop = new ReflectionProperty(self::class, 'gestionnaires');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->gestionnaires = array_map(
                    callback: fn($iri) => $this->iriConverter->getResourceFromIri($iri),
                    array: $this->entity->getParametre('gestionnaires') ?? [],
                );
            }
            return $this->gestionnaires ?? [];
        }
    }

    /**
     * @var ProfilBeneficiaire[]
     */
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public array $profils {
        get {
            $prop = new ReflectionProperty(self::class, 'profils');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->profils = array_map(
                    callback: fn($iri) => $this->iriConverter->getResourceFromIri($iri),
                    array: $this->entity->getParametre('profils') ?? [],
                );
            }
            return $this->profils ?? [];
        }
    }

    /**
     * @var Composante[]
     */
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public array $composantes {
        get {
            $prop = new ReflectionProperty(self::class, 'composantes');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->composantes = array_map(
                    callback: fn($iri) => $this->iriConverter->getResourceFromIri($iri),
                    array: $this->entity->getParametre('composantes') ?? [],
                );
            }
            return $this->composantes ?? [];
        }
    }

    /**
     * @var Formation[]
     */
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public array $formations {
        get {
            $prop = new ReflectionProperty(self::class, 'formations');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->formations = array_map(
                    callback: fn($iri) => $this->iriConverter->getResourceFromIri($iri),
                    array: $this->entity->getParametre('formations') ?? [],
                );
            }
            return $this->formations ?? [];
        }
    }

    public function getuid(): string
    {
        return $this->demandeur->uid;
    }

    public function __construct(
        private readonly ?Bilan $entity = null,
        private readonly ?IriConverterInterface $iriConverter = null,
    ) {}
}
