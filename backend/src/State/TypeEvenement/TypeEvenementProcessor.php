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

namespace App\State\TypeEvenement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\TypeEvenement;
use App\Repository\TauxHoraireRepository;
use App\Repository\TypeEvenementRepository;
use App\State\MappedEntityProcessor;
use App\State\TransformerService;
use ReflectionException;

/** @mago-ignore analysis:unused-property */
readonly class TypeEvenementProcessor implements ProcessorInterface
{
    public function __construct(
        private TypeEvenementRepository $typeEvenementRepository,
        private TauxHoraireRepository $tauxHoraireRepository,
        private TransformerService $transformerService,
    ) {}

    /**
     * @param \App\ApiResource\TypeEvenement $data
     * @param Operation                      $operation
     * @param array                          $uriVariables
     * @param array                          $context
     * @return array|mixed|object|null
     * @throws ReflectionException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        //POST et PATCH
        $entity = match ($data->id) {
            null => new TypeEvenement(),
            default => $this->typeEvenementRepository->find($data->id),
        };

        $entity->setLibelle($data->libelle);
        $entity->setActif($data->actif);
        $entity->setVisibleParDefaut($data->visibleParDefaut);
        $entity->setCouleur($data->couleur);
        $entity->setAvecValidation($data->avecValidation);
        $entity->setForfait($data->forfait);

        //on met également à jour les taux horaires
        $tauxIds = [];
        foreach ($data->tauxHoraires as $tauxHoraire) {
            $tauxExistant = $this->tauxHoraireRepository->find($tauxHoraire->id);
            $entity->addTauxHoraire($tauxExistant);
            $tauxIds[] = $tauxHoraire->id;
        }
        foreach ($entity->getTauxHoraires() as $tauxHoraire) {
            if (in_array($tauxHoraire->getId(), $tauxIds)) {
                continue;
            }
            $entity->removeTauxHoraire($tauxHoraire);
        }

        $this->typeEvenementRepository->save($entity, true);

        return $this->transformerService->transform($entity, \App\ApiResource\TypeEvenement::class);
    }
}
