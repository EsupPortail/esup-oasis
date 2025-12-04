<?php

/*
 * Copyright (c) 2024. Esup - Université de Bordeaux.
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
use App\Filter\EvenementsAValiderFilter;
use App\Filter\NestedUtilisateurFilter;
use App\Filter\NomIntervenantFilter;
use App\Filter\PreloadAssociationsFilter;
use App\Filter\UtilisateurConcerneParEvenementFilter;
use App\State\Evenement\EvenementProcessor;
use App\State\Evenement\EvenementProvider;
use App\Validator\BeneficiaireNonNullConstraint;
use App\Validator\NonEnvoyeRHConstraint;
use App\Validator\EvenementSansChevauchementConstraint;
use App\Validator\PeriodeNonBloqueeConstraint;
use App\Validator\UtilisateurBeneficiaireEvenementConstraint;
use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI
        ),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('" . self::VOIR . "', object)"
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            security: "is_granted('ROLE_PLANIFICATEUR')",
            read: false,
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('ROLE_PLANIFICATEUR')"
        ),
        new Delete(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('" . self::SUPPRIMER . "', object)",
            validationContext: ['groups' => ['deleteValidation']],
            validate: true

        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    order: ['debut' => 'ASC'],
    provider: EvenementProvider::class,
    processor: EvenementProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\Evenement::class)
)]
#[ApiFilter(SearchFilter::class, properties: [
    'type' => 'exact',
    'campus' => 'exact',
    'type.avecValidation' => 'exact',
    'type.forfait' => 'exact',
])]
#[ApiFilter(ExistsFilter::class, properties: ['periodePriseEnCompteRH', 'dateAnnulation', 'intervenant', 'beneficiaires'])]
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
#[Assert\Expression(expression: "this.type.forfait == false", message: "Type d'événement incompatible")]
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
    public ?int $id = null;

    //quoi?
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?string $libelle;
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    public TypeEvenement $type;

    //qui?
    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Utilisateur::class)])]
    public array $beneficiaires;
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?Utilisateur $intervenant = null;
    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Utilisateur::class)])]
    public array $suppleants = [];
    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Utilisateur::class)])]
    public array $enseignants;

    //quand?
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    public DateTimeInterface $debut;
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    #[Assert\GreaterThan(propertyPath: 'debut')]
    public DateTimeInterface $fin;
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public int $tempsPreparation = 0;
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public int $tempsSupplementaire = 0;

    //où?
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank]
    public Campus $campus;
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?string $salle = null;

    //comment?
    /**
     * @var TypeEquipement[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(TypeEquipement::class)])]
    public array $equipements = [];

    //état
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\GreaterThan(propertyPath: 'dateCreation')]
    public ?DateTimeInterface $dateAnnulation = null;
    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateEnvoiRH = null;

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateCreation = null;
    #[Groups([self::GROUP_OUT])]
    public Utilisateur $utilisateurCreation;

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateModification = null;
    #[Groups([self::GROUP_OUT])]
    public ?Utilisateur $utilisateurModification = null;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[ApiProperty(security: 'is_granted("ROLE_GESTIONNAIRE")')]
    public ?bool $valide = null;

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateValidation = null;

}