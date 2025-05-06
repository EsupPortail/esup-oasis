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

namespace App\State\DecisionAmenagementExamens;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\DecisionAmenagementExamens;
use App\State\AbstractEntityProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DecisionAmenagementExamensProvider extends AbstractEntityProvider
{
    public function __construct(private readonly DecisionAmenagementManager                                                   $decisionAmenagementManager,
                                #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')] ProviderInterface       $itemProvider,
                                #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')] ProviderInterface $collectionProvider)
    {

        parent::__construct($itemProvider, $collectionProvider);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        //support de GET seulement, avec en entrée uid et année
        $entity = $this->decisionAmenagementManager->parUidEtAnnee($uriVariables['uid'], (int)$uriVariables['annee']);

        return match ($entity) {
            null => null,
            default => $this->transform($entity)
        };
    }

    protected function getResourceClass(): string
    {
        return DecisionAmenagementExamens::class;
    }

    protected function getEntityClass(): string
    {
        return \App\Entity\DecisionAmenagementExamens::class;
    }

    /**
     * @param \App\Entity\DecisionAmenagementExamens $entity
     * @return DecisionAmenagementExamens
     */
    public function transform($entity): DecisionAmenagementExamens
    {
        $resource = new DecisionAmenagementExamens();
        $resource->id = $entity->getId();
        $resource->uid = $entity->getBeneficiaire()->getUid();
        $resource->etat = $entity->getEtat();
        $resource->annee = (int)$entity->getDebut()->format('Y');
        $resource->urlContenu = match ($entity->getFichier()) {
            null => null,
            default => '/fichiers/' . $entity->getFichier()->getId()
        };

        return $resource;
    }
}