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

namespace App\State\TypeAmenagement;

use App\ApiResource\CategorieAmenagement;
use App\ApiResource\OptionReponse;
use App\ApiResource\TypeAmenagement;
use App\State\AbstractEntityProvider;
use Override;

class TypeAmenagementProvider extends AbstractEntityProvider
{

    #[Override] protected function getResourceClass(): string
    {
        return TypeAmenagement::class;
    }

    #[Override] protected function getEntityClass(): string
    {
        return \App\Entity\TypeAmenagement::class;
    }

    /**
     * @param \App\Entity\TypeAmenagement $entity
     * @return TypeAmenagement
     */
    #[Override] public function transform($entity): mixed
    {
        $resource = new TypeAmenagement();
        $resource->id = $entity->getId();
        $resource->libelle = $entity->getLibelle();
        $resource->libelleLong = $entity->getLibelleLong();
        $resource->actif = $entity->isActif();
        $resource->examens = $entity->isExamens();
        $resource->pedagogique = $entity->isPedagogique();
        $resource->aideHumaine = $entity->isAideHumaine();
        $resource->decision = $entity->isDecision();
        $resource->categorie = $this->transformerService->transform($entity->getCategorie(), CategorieAmenagement::class);

        return $resource;
    }

    /**
     * @param \App\Entity\TypeAmenagement $entity
     * @return OptionReponse
     */
    public function transformIntoOptionReponse(\App\Entity\TypeAmenagement $entity): OptionReponse
    {
        $resource = new OptionReponse();
        $resource->id = $entity->getId();
        $resource->libelle = $entity->getLibelle();

        return $resource;
    }

    /**
     * @return void
     */
    #[Override] protected function registerTransformations(): void
    {
        $this->transformerService->addTransformation(
            from    : $this->getEntityClass(),
            to      : OptionReponse::class,
            callback: $this->transformIntoOptionReponse(...));

        parent::registerTransformations();
    }


}