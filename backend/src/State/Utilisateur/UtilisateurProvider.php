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

namespace App\State\Utilisateur;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\BeneficiaireProfil;
use App\ApiResource\Campus;
use App\ApiResource\Competence;
use App\ApiResource\DecisionAmenagementExamens;
use App\ApiResource\Inscription;
use App\ApiResource\Service;
use App\ApiResource\Tag;
use App\ApiResource\TypeEvenement;
use App\ApiResource\Utilisateur;
use App\Entity\Beneficiaire;
use App\Filter\PreloadAssociationsFilter;
use App\Service\ErreurLdapException;
use App\State\AbstractEntityProvider;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use Exception;
use Override;
use RuntimeException;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;


class UtilisateurProvider extends AbstractEntityProvider
{
    use ClockAwareTrait;

    public function __construct(#[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')] ProviderInterface       $itemProvider,
                                #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')] ProviderInterface $collectionProvider,
                                private readonly UtilisateurManager                                                           $utilisateurManager,
                                private readonly DecisionAmenagementManager                                                   $decisionAmenagementManager,
                                private readonly TagAwareCacheInterface                                                       $cache)
    {
        parent::__construct($itemProvider, $collectionProvider);
    }


    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return Utilisateur|array|PaginatorInterface
     * @throws ErreurLdapException
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Utilisateur|array|PaginatorInterface
    {
        //recherche dans le ldap
        if (!($operation instanceof GetCollection)) {
            //mise en cache des résultats pour un utilisateur donné
            return $this->cache->get(
                key: 'utilisateur_' . $uriVariables['uid'],
                callback: function (ItemInterface $item) use ($operation, $uriVariables, $context) {
                    //todo: si pas en base, cache à durée très courte!
                    $item->expiresAfter(7200);
                    $utilisateur = $this->ldapProvide($operation, $uriVariables, $context);
                    $item->tag('utilisateur_' . $utilisateur->uid);
                    return $utilisateur;
                }
            );
        }
        if ($operation->getName() === Utilisateur::COLLECTION_URI) {
            return $this->ldapProvide($operation, $uriVariables, $context);
        }

        //GetCollection sur /beneficiaires, /intervenants ou /renforts
        if ($operation->getName() === Utilisateur::BENEFICIAIRE_COLLECTION_URI) {
            $context['filters']['beneficiairefilter'] = true;
        }

        $context['filters'][PreloadAssociationsFilter::PROPERTY] = [
            'beneficiaires' => [
                'sourceEntity' => 'root',
                'relationName' => 'beneficiaires'
            ],
            'tags_beneficiaires' => [
                'sourceEntity' => 'beneficiaires',
                'relationName' => 'tags'
            ],
            'categorie_tag' => [
                'sourceEntity' => 'tags_beneficiaires',
                'relationName' => 'categorie'
            ],
            'profilBeneficiaire' => [
                'sourceEntity' => 'beneficiaires',
                'relationName' => 'profil'
            ],
            'gestionnaire' => [
                'sourceEntity' => 'beneficiaires',
                'relationName' => 'gestionnaire'
            ],
            'decisionsAmenagementExamens' => [
                'sourceEntity' => 'root',
                'relationName' => 'decisionsAmenagementExamens'
            ],
            'typologiesHandicap' => [
                'sourceEntity' => 'beneficiaires',
                'relationName' => 'typologies'
            ],
            'inscriptions' => [
                'sourceEntity' => 'root',
                'relationName' => 'inscriptions'
            ],
            'formation' => [
                'sourceEntity' => 'inscriptions',
                'relationName' => 'formation'
            ],
            'composante' => [
                'sourceEntity' => 'formation',
                'relationName' => 'composante'
            ],
            'intervenant' => [
                'sourceEntity' => 'root',
                'relationName' => 'intervenant'
            ],
            'competences' => [
                'sourceEntity' => 'intervenant',
                'relationName' => 'competences'
            ],
            'campuses' => [
                'sourceEntity' => 'intervenant',
                'relationName' => 'campuses'
            ],
            'services' => [
                'sourceEntity' => 'root',
                'relationName' => 'services'
            ]
        ];

        return parent::provide($operation, $uriVariables, $context);
    }

    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return Utilisateur|Utilisateur[]|PaginatorInterface
     * @throws ErreurLdapException
     */
    public function ldapProvide(Operation $operation, array $uriVariables = [], array $context = []): Utilisateur|array|PaginatorInterface
    {
        // GetCollection => en mode search only
        if ($operation instanceof CollectionOperationInterface) {
            if (!isset($context['filters']['term'])) {
                if (isset($context['filters']['recherche'])) {
                    return parent::provide($operation, $uriVariables, $context);
                }
                throw new RuntimeException('Paramètre "term" ou "recherche" manquant.');
            }

            $etudiantsSeulement = (($context['filters']['exists']['numeroEtudiant'] ?? "false") == "true");

            $users = [];
            foreach ($this->utilisateurManager->search($context['filters']['term'], $etudiantsSeulement) as $user) {
                $users[] = $this->transform($user);
            }
            return $users;
        }
        //Get simple
        try {
            return $this->transform($this->utilisateurManager->parUid($uriVariables['uid']));
        } catch (UserNotFoundException) {
            throw new ItemNotFoundException('Utilisateur inconnu');
        }
    }


    /**
     * @param \App\Entity\Utilisateur $entity
     * @return Utilisateur
     * @throws Exception
     */
    public function transform($entity): Utilisateur
    {
        $user = new Utilisateur();
        //Commun
        $user->uid = $entity->getUid();
        $user->nom = $entity->getNom();
        $user->prenom = $entity->getPrenom();
        $user->emailPerso = $entity->getEmailPerso();
        $user->telPerso = $entity->getTelPerso();
        $user->contactUrgence = $entity->getContactUrgence();
        $user->email = $entity->getEmail();
        $user->roles = $entity->getRoles();
        $user->numeroEtudiant = $entity->getNumeroEtudiant();
        $user->dateNaissance = $entity->getDateNaissance();
        $user->genre = $entity->getGenre();

        //gestionnaires/renforts/admin
        //$user->admin = $entity->isAdmin();
        $user->services = array_map(fn($service) => $this->transformerService->transform($service, Service::class),
            $entity->getServices()->toArray());

        //intervenant/renfort
        $user->competences = array_map(fn($competence) => $this->transformerService->transform($competence, Competence::class),
            $entity->getIntervenant()?->getCompetences()->toArray() ?? []);
        $user->campus = array_map(fn($campus) => $this->transformerService->transform($campus, Campus::class),
            $entity->getIntervenant()?->getCampuses()->toArray() ?? []);
        $user->typesEvenements = array_map(fn($type) => $this->transformerService->transform($type, TypeEvenement::class),
            $entity->getIntervenant()?->getTypesEvenements()->toArray() ?? []);
        $user->intervenantDebut = $entity->getIntervenant()?->getDebut();
        $user->intervenantFin = $entity->getIntervenant()?->getFin();

        //Beneficiaires
        $user->profils = array_map(fn($beneficiaire) => $this->transformerService->transform($beneficiaire, BeneficiaireProfil::class),
            $entity->getBeneficiaires()->toArray() ?? []);

        $user->gestionnairesActifs = array_unique(array_reduce($entity->getBeneficiaires()->toArray(),
            fn($carry, Beneficiaire $benef) => match (true) {
                $this->now() < $benef->getDebut() || (null !== $benef->getFin() && $this->now() > $benef->getFin()) => $carry,
                default => [...($carry ?? []), $this->transformerService->transform($benef->getGestionnaire(), Utilisateur::class)]
            }
        ) ?? [], SORT_REGULAR);

        $user->gestionnairesActifs = array_values($user->gestionnairesActifs);

        //Tags
        $user->tags = array_values(array_unique(array_map(
            fn($tag) => $this->transformerService->transform($tag, Tag::class),
            array_reduce(
                $entity->getBeneficiairesActifs(),
                fn(array $carry, Beneficiaire $beneficiaire) => [...$carry, ...$beneficiaire->getTags()],
                []
            )
        ), SORT_REGULAR));

        $user->etatAvisEse = $entity->getEtatAvisEse();

        //inscriptions
        $user->inscriptions = array_map(fn($inscription) => $this->transformerService->transform($inscription, Inscription::class),
            $entity->getInscriptions()->toArray() ?? []);

        $user->boursier = $entity->isBoursier();
        $user->statutEtudiant = $entity->getStatutEtudiant();

        //abonnements notifs
        //todo: ne pas les renseigner si utilisateur pas autorisé?
        $user->abonneImmediat = $entity->isAbonneImmediat();
        $user->abonneVeille = $entity->isAbonneVeille();
        $user->abonneAvantVeille = $entity->isAbonneAvantVeille();
        $user->abonneRecapHebdo = $entity->isAbonneRecapHebdo();

        //décision d'aménagement
        $decisionEnCours = $this->decisionAmenagementManager->getDecisionCourante($entity);
        $user->decisionAmenagementAnneeEnCours = match ($decisionEnCours) {
            null => null,
            default => $this->transformerService->transform($decisionEnCours, DecisionAmenagementExamens::class)
        };

        $user->numeroAnonyme = $entity->getNumeroAnonyme();

        return $user;
    }

    protected function getResourceClass(): string
    {
        return Utilisateur::class;
    }

    protected function getEntityClass(): string
    {
        return \App\Entity\Utilisateur::class;
    }
}