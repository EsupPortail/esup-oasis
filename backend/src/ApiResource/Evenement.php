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
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
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
use App\Filter\EvenementsAValiderFilter;
use App\Filter\NestedUtilisateurFilter;
use App\Filter\NomIntervenantFilter;
use App\Filter\PreloadAssociationsFilter;
use App\Filter\UtilisateurConcerneParEvenementFilter;
use App\State\Evenement\EvenementProcessor;
use App\State\Evenement\EvenementProvider;
use App\Validator\BeneficiaireNonNullConstraint;
use App\Validator\EvenementSansChevauchementConstraint;
use App\Validator\NonEnvoyeRHConstraint;
use App\Validator\PeriodeNonBloqueeConstraint;
use App\Validator\UtilisateurBeneficiaireEvenementConstraint;
use DateTimeInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: self::COLLECTION_URI),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('" . self::VOIR . "', object)",
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            security: "is_granted('ROLE_PLANIFICATEUR')",
            read: false,
            map: false,
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('ROLE_PLANIFICATEUR')",
            map: false,
        ),
        new Delete(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('" . self::SUPPRIMER . "', object)",
            validationContext: ['groups' => ['deleteValidation']],
            validate: true,
            map: false,
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    order: ['debut' => 'ASC'],
    provider: EvenementProvider::class,
    processor: EvenementProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\Evenement::class),
)]
#[ApiFilter(SearchFilter::class, properties: [
    'type' => 'exact',
    'campus' => 'exact',
    'type.avecValidation' => 'exact',
    'type.forfait' => 'exact',
])]
#[ApiFilter(ExistsFilter::class, properties: [
    'periodePriseEnCompteRH',
    'dateAnnulation',
    'intervenant',
    'beneficiaires',
])]
#[ApiFilter(DateFilter::class, properties: ['debut', 'fin'])]
#[ApiFilter(NestedUtilisateurFilter::class, properties: [
    'intervenant' => 'intervenant.utilisateur',
    'beneficiaires' => 'beneficiaires.utilisateur',
    'suppleants' => 'suppleants.utilisateur',
    'utilisateurCreation' => 'utilisateurCreation',
])]
#[ApiFilter(EvenementsAValiderFilter::class)]
#[ApiFilter(NomIntervenantFilter::class)]
#[ApiFilter(UtilisateurConcerneParEvenementFilter::class)]
#[UtilisateurBeneficiaireEvenementConstraint]
#[EvenementSansChevauchementConstraint]
#[NonEnvoyeRHConstraint]
#[PeriodeNonBloqueeConstraint(groups: ['deleteValidation'])]
#[BeneficiaireNonNullConstraint]
#[Assert\Expression(expression: 'this.type.forfait == false', message: "Type d'événement incompatible")]
#[ApiFilter(PreloadAssociationsFilter::class)]
final class Evenement
{
    public const string COLLECTION_URI = '/evenements';
    public const string ITEM_URI = '/evenements/{id}';
    public const string GROUP_OUT = 'evenement:out';
    public const string GROUP_IN = 'evenement:in';
    public const string SUPPRIMER = 'SUPPRIMER_EVENEMENT';
    public const string VOIR = 'VOIR_EVENEMENT';

    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_OUT])]
    public ?int $id = null {
        get {
            if ($this->id === null && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    //quoi?
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?string $libelle = null {
        get {
            if ($this->libelle === null && $this->entity !== null) {
                $this->libelle = $this->entity->getLibelle();
            }
            return $this->libelle ?? null;
        }
    }
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    public ?TypeEvenement $type = null {
        get {
            if ($this->type === null && $this->entity !== null && $this->entity->getType()) {
                $this->type = new TypeEvenement($this->entity->getType());
            }
            return $this->type;
        }
    }

    //qui?
    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Utilisateur::class)])]
    public array $beneficiaires {
        get {
            if (!isset($this->beneficiaires) && $this->entity !== null) {
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
    public ?Utilisateur $intervenant = null {
        get {
            if ($this->intervenant === null && $this->entity !== null && $this->entity->getIntervenant()) {
                $this->intervenant = new Utilisateur($this->entity->getIntervenant()->getUtilisateur());
            }
            return $this->intervenant ?? null;
        }
    }
    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Utilisateur::class)])]
    public array $suppleants = [] {
        get {
            if (empty($this->suppleants) && $this->entity !== null) {
                $this->suppleants = array_map(
                    fn($s) => new Utilisateur($s->getUtilisateur()),
                    $this->entity->getSuppleants()->toArray(),
                );
            }
            return $this->suppleants ?? [];
        }
    }
    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Utilisateur::class)])]
    public array $enseignants = [] {
        get {
            if (empty($this->enseignants) && $this->entity !== null) {
                $this->enseignants = array_map(
                    fn($e) => new Utilisateur($e),
                    $this->entity->getEnseignants()->toArray(),
                );
            }
            return $this->enseignants ?? [];
        }
    }

    //quand?
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    public ?DateTimeInterface $debut = null {
        get {
            if ($this->debut === null && $this->entity !== null) {
                $this->debut = $this->entity->getDebut();
            }
            return $this->debut;
        }
    }
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    #[Assert\GreaterThan(propertyPath: 'debut')]
    public ?DateTimeInterface $fin = null {
        get {
            if ($this->fin === null && $this->entity !== null) {
                $this->fin = $this->entity->getFin();
            }
            return $this->fin;
        }
    }
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?int $tempsPreparation = null {
        get {
            if (null === $this->tempsPreparation && $this->entity !== null) {
                $this->tempsPreparation = $this->entity->getTempsPreparation();
            }
            return $this->tempsPreparation ?? 0;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?int $tempsSupplementaire = null {
        get {
            if (null === $this->tempsSupplementaire && $this->entity !== null) {
                $this->tempsSupplementaire = $this->entity->getTempsSupplementaire();
            }
            return $this->tempsSupplementaire ?? 0;
        }
    }

    //où?
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    public ?Campus $campus = null {
        get {
            if ($this->campus === null && $this->entity !== null && $this->entity->getCampus()) {
                $this->campus = new Campus($this->entity->getCampus());
            }
            return $this->campus;
        }
    }
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?string $salle = null {
        get {
            if ($this->salle === null && $this->entity !== null) {
                $this->salle = $this->entity->getSalle();
            }
            return $this->salle ?? null;
        }
    }

    //comment?
    /**
     * @var TypeEquipement[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(TypeEquipement::class)])]
    public array $equipements = [] {
        get {
            if (empty($this->equipements) && $this->entity !== null && $this->entity->getEquipements() !== null) {
                $this->equipements = array_map(
                    fn($e) => new TypeEquipement($e),
                    $this->entity->getEquipements()->toArray(),
                );
            }
            return $this->equipements ?? [];
        }
    }

    //état
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\GreaterThan(propertyPath: 'dateCreation')]
    public ?DateTimeInterface $dateAnnulation = null {
        get {
            if ($this->dateAnnulation === null && $this->entity !== null) {
                $this->dateAnnulation = $this->entity->getDateAnnulation();
            }
            return $this->dateAnnulation ?? null;
        }
    }
    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateEnvoiRH = null {
        get {
            if ($this->dateEnvoiRH === null && $this->entity !== null) {
                $this->dateEnvoiRH = $this->entity->getDateEnvoiRH();
            }
            return $this->dateEnvoiRH ?? null;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateCreation = null {
        get {
            if ($this->dateCreation === null && $this->entity !== null) {
                $this->dateCreation = $this->entity->getDateCreation();
            }
            return $this->dateCreation ?? null;
        }
    }
    #[Groups([self::GROUP_OUT])]
    public Utilisateur $utilisateurCreation {
        get {
            if (
                !isset($this->utilisateurCreation)
                && $this->entity !== null
                && $this->entity->getUtilisateurCreation()
            ) {
                $this->utilisateurCreation = new Utilisateur($this->entity->getUtilisateurCreation());
            }
            return $this->utilisateurCreation;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateModification = null {
        get {
            if ($this->dateModification === null && $this->entity !== null) {
                $this->dateModification = $this->entity->getDateModification();
            }
            return $this->dateModification ?? null;
        }
    }
    #[Groups([self::GROUP_OUT])]
    public ?Utilisateur $utilisateurModification = null {
        get {
            if (
                $this->utilisateurModification === null
                && $this->entity !== null
                && $this->entity->getUtilisateurModification()
            ) {
                $this->utilisateurModification = new Utilisateur($this->entity->getUtilisateurModification());
            }
            return $this->utilisateurModification ?? null;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[ApiProperty(security: 'is_granted("ROLE_GESTIONNAIRE")')]
    public ?bool $valide = null {
        get {
            if ($this->valide === null && $this->entity !== null) {
                $this->valide = null !== $this->entity->getDateValidation();
            }
            return $this->valide ?? null;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateValidation = null {
        get {
            if ($this->dateValidation === null && $this->entity !== null) {
                $this->dateValidation = $this->entity->getDateValidation();
            }
            return $this->dateValidation ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\Evenement $entity = null,
    ) {}
}
