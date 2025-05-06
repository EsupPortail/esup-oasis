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

namespace App\State\Commission;

use App\ApiResource\Commission;
use App\State\AbstractEntityProvider;
use Exception;

class CommissionProvider extends AbstractEntityProvider
{

    protected function getResourceClass(): string
    {
        return Commission::class;
    }

    protected function getEntityClass(): string
    {
        return \App\Entity\Commission::class;
    }

    /**
     * @param \App\Entity\Commission $entity
     * @return Commission
     * @throws Exception
     */
    public function transform($entity): mixed
    {
        $resource = new Commission();
        $resource->id = $entity->getId();
        $resource->libelle = $entity->getLibelle();
        $resource->actif = $entity->isActif();
        return $resource;
    }
}