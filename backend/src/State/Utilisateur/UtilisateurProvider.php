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

namespace App\State\Utilisateur;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\DecisionAmenagementExamens;
use App\ApiResource\InfoComplementaire;
use App\ApiResource\Utilisateur;
use App\Entity\Beneficiaire;
use App\Service\ErreurLdapException;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use App\State\MappedCollectionPaginator;
use Override;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UtilisateurProvider implements ProviderInterface
{
    use ClockAwareTrait;

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private readonly ProviderInterface $itemProvider,
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private readonly ProviderInterface $collectionProvider,
        private readonly UtilisateurManager $utilisateurManager,
        private readonly DecisionAmenagementManager $decisionAmenagementManager,
    ) {}

    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return Utilisateur|array|PaginatorInterface
     * @throws ErreurLdapException
     * @throws InvalidArgumentException
     */
    #[Override]
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): Utilisateur|array|PaginatorInterface {
        //recherche dans le ldap
        if (!$operation instanceof GetCollection) {
            return $this->ldapProvide($operation, $uriVariables, $context);
        }
        if ($operation->getName() === Utilisateur::COLLECTION_URI) {
            return $this->ldapProvide($operation, $uriVariables, $context);
        }

        $results = $this->collectionProvider->provide($operation, $uriVariables, $context);
        assert($results instanceof PaginatorInterface);
        //ici on veut forcer la mise en cache "en masse" des infos complémentaires, pour éviter de nombreuses requêtes séparées
        $this->utilisateurManager->prechargerInfosComplementaires($results);

        return new MappedCollectionPaginator($results, $this->transformWithDecision(...));
    }

    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return Utilisateur|Utilisateur[]|PaginatorInterface
     * @throws ErreurLdapException
     */
    public function ldapProvide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): Utilisateur|array|PaginatorInterface {
        // GetCollection => en mode search only
        if ($operation instanceof CollectionOperationInterface) {
            if (!isset($context['filters']['term'])) {
                if (isset($context['filters']['recherche'])) {
                    $results = $this->collectionProvider->provide($operation, $uriVariables, $context);
                    assert($results instanceof PaginatorInterface);
                    return new MappedCollectionPaginator($results, $this->transformWithDecision(...));
                }
                throw new RuntimeException('Paramètre "term" ou "recherche" manquant.');
            }

            $etudiantsSeulement = ($context['filters']['exists']['numeroEtudiant'] ?? 'false') == 'true';

            $users = [];
            foreach ($this->utilisateurManager->search($context['filters']['term'], $etudiantsSeulement) as $user) {
                $users[] = $this->transformWithDecision($user);
            }
            return $users;
        }
        //Get simple
        try {
            return $this->transformWithDecision($this->utilisateurManager->parUid($uriVariables['uid']));
        } catch (UserNotFoundException) {
            throw new ItemNotFoundException('Utilisateur inconnu');
        }
    }

    public function transformWithDecision(\App\Entity\Utilisateur $entity): Utilisateur
    {
        $utilisateur = new Utilisateur($entity);
        $decisionEnCours = $this->decisionAmenagementManager->getDecisionCourante($entity);
        $utilisateur->decisionAmenagementAnneeEnCours = match ($decisionEnCours) {
            null => null,
            default => new DecisionAmenagementExamens($decisionEnCours),
        };

        $utilisateur->gestionnairesActifs = array_unique(array_reduce($entity->getBeneficiaires()->toArray(), fn(
            $carry,
            Beneficiaire $benef,
        ) => match (true) {
            $this->now() < $benef->getDebut() || null !== $benef->getFin() && $this->now() > $benef->getFin() => $carry,
            default => [
                ...($carry ?? []),
                new Utilisateur($benef->getGestionnaire()),
            ],
        }) ?? [], SORT_REGULAR);

        if ($entity->getNumeroEtudiant()) {
            foreach ($this->utilisateurManager->infosComplementaires($entity) as $cle => $valeur) {
                $utilisateur->infosComplementaires[] = new InfoComplementaire($cle, $valeur);
            }
        }

        return $utilisateur;
    }
}
