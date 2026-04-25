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

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
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
use App\Entity\Beneficiaire;
use App\Filter\InterventionForfaitNomIntervenantOrderFilter;
use App\Filter\NestedUtilisateurFilter;
use App\Filter\NomIntervenantFilter;
use App\Filter\PreloadAssociationsFilter;
use App\State\InterventionForfait\InterventionForfaitProcessor;
use App\State\InterventionForfait\InterventionForfaitProvider;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['id']),
        new GetCollection(uriTemplate: self::COLLECTION_URI),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            securityPostDenormalize: "is_granted('" . self::AJOUTER_INTERVENTION . "', object)",
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('" . self::MODIFIER_INTERVENTION . "', [previous_object, object])",
        ),
        new Delete(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('" . self::SUPPRIMER_INTERVENTION . "', object)",
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    order: ['periode.debut' => 'ASC'],
    provider: InterventionForfaitProvider::class,
    processor: InterventionForfaitProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\InterventionForfait::class),
)]
#[ApiFilter(SearchFilter::class, properties: ['periode', 'type'])]
#[ApiFilter(NestedUtilisateurFilter::class, properties: [
    'intervenant' => 'intervenant.utilisateur',
    'utilisateurCreation' => 'utilisateurCreation',
])]
#[ApiFilter(NomIntervenantFilter::class)]
#[ApiFilter(InterventionForfaitNomIntervenantOrderFilter::class, properties: ['intervenant.utilisateur.nom'])]
#[ApiFilter(DateFilter::class, properties: ['periode.debut', 'periode.fin'])]
#[Assert\Expression(expression: 'this.type.forfait == true', message: "Type d'événement incompatible")]
#[ApiFilter(PreloadAssociationsFilter::class)]
class InterventionForfait
{
    public const string COLLECTION_URI = '/interventions_forfait';
    public const string ITEM_URI = self::COLLECTION_URI . '/{id}';
    public const string GROUP_IN = 'forfait:in';
    public const string GROUP_OUT = 'forfait:out';
    public const string MODIFIER_INTERVENTION = 'MODIFIER_INTERVENTION';
    public const string SUPPRIMER_INTERVENTION = 'SUPPRIMER_INTERVENTION';
    public const string AJOUTER_INTERVENTION = 'AJOUTER_INTERVENTION';

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
    #[Assert\NotNull]
    public Utilisateur $intervenant {
        get {
            $prop = new ReflectionProperty(self::class, 'intervenant');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getIntervenant()) {
                $this->intervenant = new Utilisateur($this->entity->getIntervenant()->getUtilisateur());
            }
            return $this->intervenant;
        }
    }

    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Utilisateur::class)])]
    public array $beneficiaires {
        get {
            $prop = new ReflectionProperty(self::class, 'beneficiaires');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $utilisateursBeneficiaires = array_reduce(
                    $this->entity->getBeneficiaires()->toArray(),
                    function ($carry, Beneficiaire $item) {
                        if (!array_key_exists($item->getUtilisateur()->getId(), $carry)) {
                            $carry[$item->getUtilisateur()->getId()] = $item->getUtilisateur();
                        }
                        return $carry;
                    },
                    [],
                );
                $this->beneficiaires = array_values(array_map(
                    fn($b) => new Utilisateur($b),
                    $utilisateursBeneficiaires,
                ));
            }
            return $this->beneficiaires ?? [];
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotNull]
    public PeriodeRH $periode {
        get {
            $prop = new ReflectionProperty(self::class, 'periode');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->periode = new PeriodeRH($this->entity->getPeriode());
            }
            return $this->periode;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotNull]
    public TypeEvenement $type {
        get {
            $prop = new ReflectionProperty(self::class, 'type');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getType()) {
                $this->type = new TypeEvenement($this->entity->getType());
            }
            return $this->type;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 5)]
    #[Assert\Type('numeric')]
    #[Assert\LessThan(10000)] //decimal(5,1)
    #[Assert\Positive]
    public string $heures {
        get {
            $prop = new ReflectionProperty(self::class, 'heures');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->heures = $this->entity->getHeures() ?? '0';
            }
            return $this->heures;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public DateTimeInterface $dateCreation {
        get {
            $prop = new ReflectionProperty(self::class, 'dateCreation');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->dateCreation = $this->entity->getDateCreation();
            }
            return $this->dateCreation ?? null;
        }
    }
    #[Groups([self::GROUP_OUT])]
    public ?Utilisateur $utilisateurCreation {
        get {
            $prop = new ReflectionProperty(self::class, 'utilisateurCreation');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getUtilisateurCreation()) {
                $this->utilisateurCreation = new Utilisateur($this->entity->getUtilisateurCreation());
            }
            return $this->utilisateurCreation;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateModification {
        get {
            $prop = new ReflectionProperty(self::class, 'dateModification');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->dateModification = $this->entity->getDateModification();
            }
            return $this->dateModification ?? null;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?Utilisateur $utilisateurModification {
        get {
            $prop = new ReflectionProperty(self::class, 'utilisateurModification');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getUtilisateurModification()) {
                $this->utilisateurModification = new Utilisateur($this->entity->getUtilisateurModification());
            }
            return $this->utilisateurModification ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\InterventionForfait $entity = null,
    ) {}
}
