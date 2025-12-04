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

namespace App\State\Evenement;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Campus;
use App\ApiResource\Evenement;
use App\ApiResource\TypeEquipement;
use App\ApiResource\TypeEvenement;
use App\ApiResource\Utilisateur;
use App\Entity\ApplicationCliente;
use App\Entity\Service;
use App\Filter\PreloadAssociationsFilter;
use App\State\AbstractEntityProvider;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class EvenementProvider extends AbstractEntityProvider
{
    public function __construct(#[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')] ProviderInterface       $itemProvider,
                                #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')] ProviderInterface $collectionProvider,
                                private readonly Security                                                                     $security, private readonly TagAwareCacheInterface $cache)
    {
        parent::__construct($itemProvider, $collectionProvider);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $utilisateur = $this->security->getUser();

        if ($operation instanceof GetCollection) {
            //calcul de la clé pour mise en cache
            $cacheKey = match (true) {
                $this->security->isGranted(\App\Entity\Utilisateur::ROLE_PLANIFICATEUR) => 'evenements_role_planificateur_',
                default => 'evenements_user_' . $utilisateur->getUserIdentifier() . '_'
            };
            //Evenements à valider : différences en fonction du service de l'utilisateur!
            if (array_key_exists('filters', $context) && array_key_exists('aValider', $context['filters'])) {
                assert($utilisateur instanceof \App\Entity\Utilisateur);
                $cacheKey .= array_reduce(
                    array: $utilisateur->getServices()->toArray(),
                    callback: function ($carry, Service $item) {
                        return $carry . '_' . $item->getId();
                    },
                    initial: ''
                );
            }

            //on colle tous les filtres
            $cacheKey .= urlencode(json_encode($context['filters'] ?? []));

            //on ajoute "de force" un filtre sur l'utilisateur courant
            if (!$this->security->isGranted(ApplicationCliente::ROLE_APPLICATION_CLIENTE)) {
                $user = $utilisateur;
                $context['filters']['utilisateurConcerne'] = Utilisateur::COLLECTION_URI . '/' . $user->getUserIdentifier();
            }
        } else {
            $cacheKey = 'evenement_' . $uriVariables['id'];
        }

        return $this->cache->get(
            key: $cacheKey,
            callback: function (ItemInterface $item) use ($operation, $uriVariables, $context) {
                $context['filters'][PreloadAssociationsFilter::PROPERTY] = [
                    'intervenant' => [
                        'sourceEntity' => 'root',
                        'relationName' => 'intervenant'
                    ],
                    'utilisateur' => [
                        'sourceEntity' => 'intervenant',
                        'relationName' => 'utilisateur'
                    ],
                    'campus' => [
                        'sourceEntity' => 'root',
                        'relationName' => 'campus'
                    ],
                    'intervenantCampus' => [
                        'sourceEntity' => 'intervenant',
                        'relationName' => 'campuses'
                    ],
                    'beneficiaires' => [
                        'sourceEntity' => 'root',
                        'relationName' => 'beneficiaires'
                    ],
                    'utilisateurBenef' => [
                        'sourceEntity' => 'beneficiaires',
                        'relationName' => 'utilisateur'
                    ],
                    'intervenantBenef' => [
                        'sourceEntity' => 'utilisateurBenef',
                        'relationName' => 'intervenant',
                    ],
                    'utilisateurCreation' => [
                        'sourceEntity' => 'root',
                        'relationName' => 'utilisateurCreation'
                    ],
                    'intervenantUtilisateurCreation' => [
                        'sourceEntity' => 'utilisateurCreation',
                        'relationName' => 'intervenant'
                    ],
                    'servicesUtilisateurCreation' => [
                        'sourceEntity' => 'utilisateurCreation',
                        'relationName' => 'services'
                    ],
                    'profil' => [
                        'sourceEntity' => 'beneficiaires',
                        'relationName' => 'profil'
                    ],
                    'enseignants' => [
                        'sourceEntity' => 'root',
                        'relationName' => 'enseignants'
                    ],
                    'intervenantEnseignant' => [
                        'sourceEntity' => 'enseignants',
                        'relationName' => 'intervenant'
                    ],
                    'equipements' => [
                        'sourceEntity' => 'root',
                        'relationName' => 'equipements'
                    ],
                    'suppleants' => [
                        'sourceEntity' => 'root',
                        'relationName' => 'suppleants'
                    ]
                ];
                $result = parent::provide($operation, $uriVariables, $context);
                $item->expiresAfter(7200);
                if ($result instanceof Evenement) {
                    $item->tag('evenement_' . $result->id);
                } else {
                    //Collection - on tente de gérer les dates pour invalidation lors de créations
                    if (!array_key_exists('filters', $context) || !array_key_exists('debut', $context['filters'])
                        || !array_key_exists('fin', $context['filters'])) {
                        $item->tag('collection_evenements_sans_dates');
                    } else {
                        $start = new DatePoint(current($context['filters']['debut']));
                        $end = new DatePoint(current($context['filters']['fin']));
                        while ($start <= $end) {
                            $item->tag('collection_evenements_' . $start->format('Y-m-d'));
                            $start = $start->modify('+1 day');
                        }
                    }
                    foreach ($result as $res) {
                        $item->tag('evenement_' . $res->id);
                    }
                }

                return $result;
            }
        );


    }

    /**
     * @param \App\Entity\Evenement $entity
     * @return Evenement
     * @throws Exception
     */
    public function transform($entity): mixed
    {
        $resource = new Evenement();
        $resource->id = $entity->getId();

        //Quoi?
        $resource->type = $this->transformerService->transform($entity->getType(), TypeEvenement::class);
        $resource->libelle = $entity->getLibelle();

        //Quand?
        $resource->debut = $entity->getDebut();
        $resource->fin = $entity->getFin();
        $resource->tempsPreparation = $entity->getTempsPreparation();
        $resource->tempsSupplementaire = $entity->getTempsSupplementaire();

        //Qui?
        $resource->beneficiaires = array_map(
            fn($benef) => $this->transformerService->transform($benef->getUtilisateur(), Utilisateur::class),
            $entity->getBeneficiaires()->toArray());
        $resource->beneficiaires = array_values(array_reduce($resource->beneficiaires,
            fn($carry, Utilisateur $benef) => match (true) {
                array_key_exists($benef->uid, $carry ?? []) => $carry,
                default => [...($carry ?? []), $benef->uid => $benef]
            }
        ) ?? []);
        $resource->intervenant = match ($entity->getIntervenant()) {
            null => null,
            default => $this->transformerService->transform($entity->getIntervenant()->getUtilisateur(), Utilisateur::class),
        };
        $resource->suppleants = array_map(fn($suppleant) => $this->transformerService->transform($suppleant->getUtilisateur(), Utilisateur::class),
            $entity->getSuppleants()->toArray());
        $resource->enseignants = array_map(fn($enseignant) => $this->transformerService->transform($enseignant, Utilisateur::class),
            $entity->getEnseignants()->toArray());

        //Où?
        $resource->campus = $this->transformerService->transform($entity->getCampus(), Campus::class);
        $resource->salle = $entity->getSalle();

        //Comment
        $resource->equipements = array_map(fn($type) => $this->transformerService->transform($type, TypeEquipement::class),
            $entity->getEquipements()->toArray());

        //état
        $resource->dateAnnulation = $entity->getDateAnnulation();
        $resource->dateEnvoiRH = $entity->getPeriodePriseEnCompteRH()?->getFin();
        $resource->dateCreation = $entity->getDateCreation();
        $resource->dateModification = $entity->getDateModification();
        $resource->utilisateurCreation = $this->transformerService->transform($entity->getUtilisateurCreation(), Utilisateur::class);
        $resource->utilisateurModification = match ($utilisateurModif = $entity->getUtilisateurModification()) {
            null => null,
            default => $this->transformerService->transform($utilisateurModif, Utilisateur::class)
        };

        if ($entity->getType()->getId() === \App\Entity\TypeEvenement::TYPE_RENFORT) {
            $resource->valide = (null !== $entity->getDateValidation());
            $resource->dateValidation = $entity->getDateValidation();
        }

        return $resource;
    }

    protected function getResourceClass(): string
    {
        return Evenement::class;
    }

    protected function getEntityClass(): string
    {
        return \App\Entity\Evenement::class;
    }
}