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

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Filter\CampagneNonArchiveeFilter;
use App\Filter\DemandeDisciplineSportiveFilter;
use App\Filter\DemandeFormatFilter;
use App\Filter\DerniereInscriptionSearchFilter;
use App\Filter\NestedUtilisateurFilter;
use App\State\Demande\DemandeProvider;
use App\State\Demande\DemandesUtilisateurProvider;
use App\State\Demande\PatchDemandeProcessor;
use App\State\Demande\PostDemandeProcessor;
use App\Validator\DemandeUniqueParCampagneConstraint;
use App\Validator\DemandeWorkflowConstraint;
use DateTimeInterface;
use Exception;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('" . self::VOIR_DEMANDE . "', object)"
        ),
        new GetCollection(
            uriTemplate: self::COLLECTION_URI,
            forceEager: false
        ),
        new GetCollection(
            uriTemplate: self::COLLECTION_UTILISATEUR_URI,
            uriVariables: ['uid'],
            security: "is_granted('ROLE_GESTIONNAIRE') or request.get('uid') == user.getUid()",
            forceEager: false,
            provider: DemandesUtilisateurProvider::class,
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            denormalizationContext: ['groups' => [self::GROUP_IN]],
            securityPostDenormalize: "is_granted('ROLE_GESTIONNAIRE') or object.demandeur.uid == user.getUid()",
            read: false,
            processor: PostDemandeProcessor::class,
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            denormalizationContext: ['groups' => [self::GROUP_CHANGEMENT_ETAT]],
            securityPostDenormalize: "is_granted('" . self::MAJ_DEMANDE . "', [previous_object, object])",
            processor: PatchDemandeProcessor::class
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Demandes']),
    provider: DemandeProvider::class,
    stateOptions: new Options(entityClass: \App\Entity\Demande::class),
)]
#[ApiFilter(SearchFilter::class, properties: [
    'demandeur.nom' => 'ipartial',
    'demandeur.prenom' => 'ipartial',
    'etat' => 'exact',
    'campagne.typeDemande' => 'exact',
    'campagne.typeDemande.libelle' => 'ipartial',
    'campagne' => 'exact',
])]
#[ApiFilter(NestedUtilisateurFilter::class, properties: [
    'demandeur' => 'demandeur',
    'gestionnaire' => 'demandeur.beneficiaires.gestionnaire',
])]
#[ApiFilter(DerniereInscriptionSearchFilter::class, properties: [
    'libelleComposante' => [
        'type' => 'string',
        'mapping' => 'demandeur.inscriptions.formation.composante.libelle',
        'desc' => 'libellé de la composante',
        'etudiant' => 'demandeur',
    ],
    'libelleFormation' => [
        'type' => 'string',
        'mapping' => 'demandeur.inscriptions.formation.libelle',
        'desc' => 'libellé de la formation',
        'etudiant' => 'demandeur',
    ],
    'composante' => [
        'type' => 'relation',
        'mapping' => 'demandeur.inscriptions.formation.composante',
        'desc' => 'libellé de la composante',
        'etudiant' => 'demandeur',
    ],
    'formation' => [
        'type' => 'relation',
        'mapping' => 'demandeur.inscriptions.formation',
        'desc' => 'libellé de la formation',
        'etudiant' => 'demandeur',
    ],
])]
#[ApiFilter(DemandeDisciplineSportiveFilter::class)]
#[ApiFilter(OrderFilter::class, properties: ['demandeur.nom', 'dateDepot'])]
#[ApiFilter(DemandeFormatFilter::class)]
#[ApiFilter(CampagneNonArchiveeFilter::class)]
#[DemandeUniqueParCampagneConstraint]
#[DemandeWorkflowConstraint]
class Demande
{
    public const string ITEM_URI = '/demandes/{id}';
    public const string COLLECTION_URI = '/demandes';
    public const string COLLECTION_UTILISATEUR_URI = '/utilisateurs/{uid}/demandes';
    public const string GROUP_OUT = 'demande:out';
    public const string GROUP_IN = 'demande:in';
    public const string GROUP_CHANGEMENT_ETAT = 'demande:modif';
    public const string MAJ_DEMANDE = 'MAJ_DEMANDE';
    public const string VOIR_DEMANDE = 'VOIR_DEMANDE';

    #[Groups([self::GROUP_OUT])]
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotNull(message: 'Impossible si le DemandeDenormalizer fait son job')]
    public ?Utilisateur $demandeur = null;

    #[Groups([self::GROUP_OUT])]
    public CampagneDemande $campagne;

    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    #[Assert\NotNull(message: 'Pour créer une demande il faut en spécifier le type.')]
    public TypeDemande $typeDemande;

    #[Groups([self::GROUP_OUT])]
    public ?int $idCommission = null;

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateDepot;

    #[Groups([self::GROUP_OUT, self::GROUP_CHANGEMENT_ETAT])]
    public EtatDemande $etat;

    /**
     * @var EtapeDemandeEtudiant[]
     */
    #[Groups([self::GROUP_OUT])]
    public array $etapes;

    #[Groups([self::GROUP_OUT])]
    public bool $complete = false;

    #[Groups([self::GROUP_CHANGEMENT_ETAT])]
    public ?string $commentaireChangementEtat = null;

    #[Groups([self::GROUP_CHANGEMENT_ETAT, self::GROUP_OUT])]
    public ?ProfilBeneficiaire $profilAttribue = null;

    #[Groups([self::GROUP_CHANGEMENT_ETAT, self::GROUP_OUT])]
    public ?string $commentaire = null;

    /**
     * @param mixed $question
     * @return QuestionDemande
     * @throws Exception
     */
    public function getQuestionDemande(\App\Entity\Question $question): QuestionDemande
    {
        foreach ($this->etapes as $etape) {
            foreach ($etape->questions as $questionDemande) {
                if ($questionDemande->id == $question->getId()) {
                    return $questionDemande;
                }
            }
        }
        throw new Exception("pas de QuestionDemande pour cette Question");
    }
}