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


use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\OpenApi\Model\Operation;
use App\Filter\BeneficiaireActifTagFilter;
use App\Filter\BeneficiaireAvecAmenagementEnCoursFilter;
use App\Filter\BeneficiaireFilter;
use App\Filter\CaseInsensitiveOrderFilter;
use App\Filter\CategorieAmenagementEnCoursFilter;
use App\Filter\ComposanteFormationFilter;
use App\Filter\DomaineAmenagementEnCoursFilter;
use App\Filter\EtatAvisEseUtilisateurFilter;
use App\Filter\EtatDecisionAmenagementFilter;
use App\Filter\IntervenantArchiveFilter;
use App\Filter\IntervenantDisponibleFilter;
use App\Filter\IntervenantFilter;
use App\Filter\IntervenantOrderedByBeneficiaireFilter;
use App\Filter\LdapSearchFilter;
use App\Filter\LibCampusIntervenantFilter;
use App\Filter\LibComposanteBeneficiaireFilter;
use App\Filter\NestedFieldSearchFilter;
use App\Filter\NomGestionnaireFilter;
use App\Filter\PreloadAssociationsFilter;
use App\Filter\ProfilBeneficiaireFilter;
use App\Filter\RenfortFilter;
use App\Filter\TypeAmenagementEnCoursFilter;
use App\Filter\UtilisateurExistantSearchFilter;
use App\Filter\UtilisateurRoleFilter;
use App\Service\ErreurLdapException;
use App\State\Amenagement\AmenagementsParUtilisateurProvider;
use App\State\Utilisateur\BeneficiaireProvider;
use App\State\Utilisateur\IntervenantProvider;
use App\State\Utilisateur\RenfortProvider;
use App\State\Utilisateur\UtilisateurProcessor;
use App\State\Utilisateur\UtilisateurProvider;
use App\State\Utilisateur\UtilisateurRoleProvider;
use App\Validator\NumeroAnonymeUniqueConstraint;
use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI,
            name: self::COLLECTION_URI,
            provider: UtilisateurProvider::class,
        ),
        new GetCollection(
            uriTemplate: self::BENEFICIAIRE_COLLECTION_URI,
            name: self::BENEFICIAIRE_COLLECTION_URI,
            provider: BeneficiaireProvider::class
        ),
        new GetCollection(
            uriTemplate: self::INTERVENANT_COLLECTION_URI,
            name: self::INTERVENANT_COLLECTION_URI,
            provider: IntervenantProvider::class
        ),
        new GetCollection(
            uriTemplate: self::RENFORT_COLLECTION_URI,
            name: self::RENFORT_COLLECTION_URI,
            provider: RenfortProvider::class
        ),
        new GetCollection(
            uriTemplate: '/roles/{roleId}/utilisateurs',
            uriVariables: ['roleId'],
            security: "is_granted('" . self::LIST_BY_ROLE . "', request)",
            provider: UtilisateurRoleProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/amenagements/utilisateurs',
            normalizationContext: ['groups' => [self::AMENAGEMENTS_UTILISATEURS_OUT]],
            security: "is_granted('" . Amenagement::VOIR_AMENAGEMENTS . "')",
            filters: [
                BeneficiaireAvecAmenagementEnCoursFilter::class,
                CategorieAmenagementEnCoursFilter::class,
                TypeAmenagementEnCoursFilter::class,
                DomaineAmenagementEnCoursFilter::class,
            ],
            provider: AmenagementsParUtilisateurProvider::class
        ),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['uid'],
            provider: UtilisateurProvider::class,
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            securityPostDenormalize: "is_granted('" . self::CAN_PATCH_USER . "', [previous_object, object])",
            provider: UtilisateurProvider::class
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Utilisateurs']),
    exceptionToStatus: [ErreurLdapException::class => 400],
    processor: UtilisateurProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\Utilisateur::class)
)]
#[ApiFilter(LdapSearchFilter::class)]
#[ApiFilter(RenfortFilter::class)]
#[ApiFilter(BeneficiaireFilter::class)]
#[ApiFilter(IntervenantFilter::class)]
#[ApiFilter(IntervenantOrderedByBeneficiaireFilter::class)]
#[ApiFilter(IntervenantDisponibleFilter::class)]
#[ApiFilter(UtilisateurExistantSearchFilter::class, properties: ['nom', 'prenom', 'uid', 'numeroAnonyme' => 'int'])]
#[ApiFilter(BeneficiaireActifTagFilter::class, properties: ['tags' => ['description' => 'filtrer par tag']])]
#[ApiFilter(SearchFilter::class, properties: [
    'nom' => 'ipartial',
    'prenom' => 'ipartial',
    'intervenant.typesEvenements',
    'intervenant.campuses',
    'intervenant.competences',
])]
#[ApiFilter(ProfilBeneficiaireFilter::class)]
#[ApiFilter(LibCampusIntervenantFilter::class)]
#[ApiFilter(LibComposanteBeneficiaireFilter::class)]
#[ApiFilter(NomGestionnaireFilter::class)]
#[ApiFilter(IntervenantArchiveFilter::class)]
#[ApiFilter(CaseInsensitiveOrderFilter::class, properties: ['nom'])]
#[ApiFilter(BooleanFilter::class, properties: ['beneficiaires.avecAccompagnement'])]
#[ApiFilter(NestedFieldSearchFilter::class, properties: [
    'gestionnaire' => [
        'type' => 'string',
        'mapping' => 'beneficiaires.gestionnaire',
        'desc' => 'gestionnaire du bénéficiaire',
    ]])]
#[ApiFilter(ExistsFilter::class, properties: ['numeroEtudiant'])]
#[ApiFilter(ComposanteFormationFilter::class, properties: ['composante', 'formation'])]
#[ApiFilter(EtatAvisEseUtilisateurFilter::class)]
#[ApiFilter(EtatDecisionAmenagementFilter::class)]
#[ApiFilter(UtilisateurRoleFilter::class)]
#[ApiFilter(PreloadAssociationsFilter::class)]
#[NumeroAnonymeUniqueConstraint]
final class Utilisateur
{
    public const string COLLECTION_URI = '/utilisateurs';
    public const string ITEM_URI = self::COLLECTION_URI . '/{uid}';
    public const string INTERVENANT_COLLECTION_URI = '/intervenants';
    public const string BENEFICIAIRE_COLLECTION_URI = '/beneficiaires';
    public const string RENFORT_COLLECTION_URI = '/renforts';
    public const string GROUP_OUT = 'utilisateur:out';
    public const string GROUP_IN = 'utilisateur:in';
    public const string AMENAGEMENTS_UTILISATEURS_OUT = 'amenagements_utilisateurs:out';

    public const string CAN_PATCH_USER = 'CAN_PATCH_USER';
    public const string LIST_BY_ROLE = 'LIST_BY_ROLE';
    public const string VOIR_INFOS_PERSO = 'VOIR_INFOS_PERSO';

    public const array TOUS_ROLES = [
        \App\Entity\Utilisateur::ROLE_ADMIN,
        \App\Entity\Utilisateur::ROLE_GESTIONNAIRE,
        \App\Entity\Utilisateur::ROLE_RENFORT,
        \App\Entity\Utilisateur::ROLE_USER,
        \App\Entity\Utilisateur::ROLE_BENEFICIAIRE,
        \App\Entity\Utilisateur::ROLE_DEMANDEUR,
        \App\Entity\Utilisateur::ROLE_INTERVENANT,
        \App\Entity\Utilisateur::ROLE_PLANIFICATEUR,
        \App\Entity\Utilisateur::ROLE_ADMIN_TECHNIQUE,
        \App\Entity\Utilisateur::ROLE_MEMBRE_COMMISSION,
        \App\Entity\Utilisateur::ROLE_REFERENT_COMPOSANTE,
        \App\Entity\Utilisateur::ROLE_VALIDER_CONFORMITE_DEMANDE,
        \App\Entity\Utilisateur::ROLE_ATTRIBUER_PROFIL,
    ];

    #[ApiProperty(identifier: true)]
    #[Groups([
        self::GROUP_OUT,
        Demande::GROUP_OUT,
        self::AMENAGEMENTS_UTILISATEURS_OUT,
        Amenagement::GROUP_OUT,
    ])]
    public string $uid;

    public ?string $roleId = null {
        get => $this->roleId ?? $this->roles[0];
    }

    #[Groups([
        self::GROUP_OUT,
        self::AMENAGEMENTS_UTILISATEURS_OUT,
        ActiviteBeneficiaire::OUT,
        ActiviteIntervenant::OUT,
        Amenagement::GROUP_OUT,
    ])]
    public string $email;

    #[Groups([
        self::GROUP_OUT,
        ActiviteBeneficiaire::OUT,
        ActiviteIntervenant::OUT,
        Demande::GROUP_OUT,
        self::AMENAGEMENTS_UTILISATEURS_OUT,
        Amenagement::GROUP_OUT,
    ])]
    public string $nom;

    #[Groups([
        self::GROUP_OUT,
        ActiviteBeneficiaire::OUT,
        ActiviteIntervenant::OUT,
        Demande::GROUP_OUT,
        self::AMENAGEMENTS_UTILISATEURS_OUT,
        Amenagement::GROUP_OUT,
    ])]
    public string $prenom;

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateNaissance = null;

    #[Groups([self::GROUP_OUT])]
    public ?string $genre = null;

    #[Groups([self::GROUP_OUT, self::AMENAGEMENTS_UTILISATEURS_OUT, Amenagement::GROUP_OUT])]
    public ?int $numeroEtudiant = null;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\Email]
    #[ApiProperty(security: "object == null or object.uid == user.getUserIdentifier() or is_granted('ROLE_PLANIFICATEUR')",
        securityPostDenormalize: "is_granted('" . self::VOIR_INFOS_PERSO . "', object)"
    )]
    public ?string $emailPerso;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\Length(max: 20)]
    #[ApiProperty(security: "object == null or object.uid == user.getUserIdentifier() or is_granted('ROLE_PLANIFICATEUR')",
        securityPostDenormalize: "is_granted('" . self::VOIR_INFOS_PERSO . "', object)"
    )]
    public ?string $telPerso;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[ApiProperty(security: "object == null or object.uid == user.getUserIdentifier() or is_granted('ROLE_PLANIFICATEUR')",
        securityPostDenormalize: "is_granted('" . self::VOIR_INFOS_PERSO . "', object)"
    )]
    public ?string $contactUrgence;


    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\Choice(self::TOUS_ROLES, multiple: true)]
    public array $roles;

    /**
     * @var Service[] $services
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Service::class)])]
    public array $services;

    /**
     * @var Campus[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Campus::class)])]
    public ?array $campus;

    /**
     * @var Competence[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(Competence::class)])]
    public ?array $competences;

    /**
     * @var TypeEvenement[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(TypeEvenement::class)])]
    public ?array $typesEvenements;

    /**
     * @var BeneficiaireProfil[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(BeneficiaireProfil::class)])]
    #[ApiProperty(security: "is_granted('ROLE_GESTIONNAIRE')")]
    public array $profils;

    #[Groups([self::GROUP_OUT, self::AMENAGEMENTS_UTILISATEURS_OUT, Amenagement::GROUP_OUT])]
    #[ApiProperty(security: "is_granted('ROLE_GESTIONNAIRE')")]
    public string $etatAvisEse;

    /**
     * @var Amenagement[]
     */
    #[Groups([self::AMENAGEMENTS_UTILISATEURS_OUT])]
    public array $amenagements;

    /**
     * @var Tag[]
     */
    #[Groups([self::GROUP_OUT, self::AMENAGEMENTS_UTILISATEURS_OUT, Amenagement::GROUP_OUT])]
    #[ApiProperty(security: "is_granted('ROLE_PLANIFICATEUR')")]
    public array $tags;

    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_OUT, Amenagement::GROUP_OUT])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    public array $gestionnairesActifs;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?DateTimeInterface $intervenantDebut = null;
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?DateTimeInterface $intervenantFin = null;

    /**
     * @var Inscription[]
     */
    #[Groups([self::GROUP_OUT, Demande::GROUP_OUT, self::AMENAGEMENTS_UTILISATEURS_OUT, Amenagement::GROUP_OUT])]
    public array $inscriptions;

    #[Groups([self::GROUP_OUT])]
    #[ApiProperty(security: "object == null or object.uid == user.getUserIdentifier() or is_granted('ROLE_PLANIFICATEUR')")]
    public ?bool $boursier;

    #[Groups([self::GROUP_OUT])]
    #[ApiProperty(security: "object == null or object.uid == user.getUserIdentifier() or is_granted('ROLE_PLANIFICATEUR')")]
    public ?string $statutEtudiant = null;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[ApiProperty(security: "object == null or object.uid == user.getUserIdentifier() or is_granted('ROLE_PLANIFICATEUR')",
        securityPostDenormalize: "is_granted('" . self::VOIR_INFOS_PERSO . "', object)"
    )]
    public bool $abonneImmediat;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[ApiProperty(security: "object == null or object.uid == user.getUserIdentifier() or is_granted('ROLE_PLANIFICATEUR')",
        securityPostDenormalize: "is_granted('" . self::VOIR_INFOS_PERSO . "', object)"
    )]
    public bool $abonneVeille;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[ApiProperty(security: "object == null or object.uid == user.getUserIdentifier() or is_granted('ROLE_PLANIFICATEUR')",
        securityPostDenormalize: "is_granted('" . self::VOIR_INFOS_PERSO . "', object)"
    )]
    public bool $abonneAvantVeille;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[ApiProperty(security: "object == null or object.uid == user.getUserIdentifier() or is_granted('ROLE_PLANIFICATEUR')",
        securityPostDenormalize: "is_granted('" . self::VOIR_INFOS_PERSO . "', object)"
    )]
    public bool $abonneRecapHebdo;

    #[Groups([self::GROUP_OUT])]
    #[ApiProperty(security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "', object)")]
    public ?DecisionAmenagementExamens $decisionAmenagementAnneeEnCours;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[ApiProperty(security: "is_granted('" . self::VOIR_INFOS_PERSO . "', object)")]
    public ?int $numeroAnonyme = null;

    public function nomAffichage(): string
    {
        return ucfirst($this->prenom) . ' ' . ucfirst($this->nom);
    }

}