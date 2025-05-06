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

namespace App\State\CampagneDemande;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use App\ApiResource\CampagneDemande;
use App\ApiResource\Commission;
use App\State\AbstractEntityProvider;

class CampagneDemandeProvider extends AbstractEntityProvider
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        //todo: vérifier adéquation type demande <=> campagne?

        unset($uriVariables['typeId']);
        unset($context['uri_variables']['typeId']);

        assert($operation instanceof HttpOperation);
        $operationUriVariables = [$operation->getUriVariables()['id'] ?? null];

        return parent::provide($operation->withUriVariables($operationUriVariables), $uriVariables, $context);
    }

    protected function getResourceClass(): string
    {
        return CampagneDemande::class;
    }

    protected function getEntityClass(): string
    {
        return \App\Entity\CampagneDemande::class;
    }

    /**
     * @param \App\Entity\CampagneDemande $entity
     * @return CampagneDemande
     */
    public function transform($entity): mixed
    {
        $resource = new CampagneDemande();
        $resource->id = $entity->getId();
        $resource->libelle = $entity->getLibelle();
        $resource->debut = $entity->getDebut();
        $resource->fin = $entity->getFin();
        $resource->typeId = $entity->getTypeDemande()->getId();
        $resource->dateCommission = $entity->getDateCommission();
        $resource->dateArchivage = $entity->getDateArchivage();
        $resource->commission = match (($commission = $entity->getCommission())) {
            null => null,
            default => $this->transformerService->transform($commission, Commission::class)
        };
        $resource->anneeCible = $entity->getAnneeCible();
        return $resource;
    }
}