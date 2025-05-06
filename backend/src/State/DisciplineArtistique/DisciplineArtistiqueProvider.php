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

namespace App\State\DisciplineArtistique;

use App\ApiResource\DisciplineArtistique;
use App\ApiResource\OptionReponse;
use App\State\AbstractEntityProvider;
use Override;

class DisciplineArtistiqueProvider extends AbstractEntityProvider
{
    #[Override] protected function getResourceClass(): string
    {
        return DisciplineArtistique::class;
    }

    #[Override] protected function getEntityClass(): string
    {
        return \App\Entity\DisciplineArtistique::class;
    }

    /**
     * @param \App\Entity\DisciplineArtistique $entity
     * @return mixed
     */
    #[Override] public function transform($entity): mixed
    {
        $resource = new DisciplineArtistique();
        $resource->id = $entity->getId();
        $resource->libelle = $entity->getLibelle();
        $resource->actif = $entity->isActif();
        return $resource;
    }

    /**
     * @param \App\Entity\DisciplineArtistique $entity
     * @return OptionReponse
     */
    public function transformIntoOptionReponse(\App\Entity\DisciplineArtistique $entity): OptionReponse
    {
        $resource = new OptionReponse();
        $resource->id = $entity->getId();
        $resource->libelle = $entity->getLibelle();
        $resource->questionsLiees = [];
        return $resource;
    }

    #[Override] protected function registerTransformations(): void
    {
        $this->transformerService->addTransformation(
            from: $this->getEntityClass(),
            to: OptionReponse::class,
            callback: $this->transformIntoOptionReponse(...));
        parent::registerTransformations();
    }

}