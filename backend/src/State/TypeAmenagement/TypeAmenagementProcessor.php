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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\TypeAmenagement;
use App\Repository\CategorieAmenagementRepository;
use App\Repository\TypeAmenagementRepository;
use Override;

readonly class TypeAmenagementProcessor implements ProcessorInterface
{
    public function __construct(
        private CategorieAmenagementRepository $categorieAmenagementRepository,
        private TypeAmenagementRepository $typeAmenagementRepository,
    ) {}

    #[Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): \App\ApiResource\TypeAmenagement {
        /**
         * Uniquement POST et PATCH
         */

        $entity = match ($data->id) {
            null => new TypeAmenagement(),
            default => $this->typeAmenagementRepository->find($data->id),
        };

        $entity->setLibelle($data->libelle);
        $entity->setLibelleLong($data->libelleLong);
        $entity->setActif($data->actif);
        $entity->setExamens($data->examens);
        $entity->setAideHumaine($data->aideHumaine);
        $entity->setPedagogique($data->pedagogique);
        $entity->setCategorie($this->categorieAmenagementRepository->find($data->categorie->id));
        $entity->setDecision($data->decision);

        $this->typeAmenagementRepository->save($entity, true);

        $resource = new \App\ApiResource\TypeAmenagement($entity);

        return $resource;
    }
}
