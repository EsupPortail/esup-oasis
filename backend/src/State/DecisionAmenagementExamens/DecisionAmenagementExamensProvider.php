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

namespace App\State\DecisionAmenagementExamens;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\DecisionAmenagementExamens;

readonly class DecisionAmenagementExamensProvider implements ProviderInterface
{
    public function __construct(
        private DecisionAmenagementManager $decisionAmenagementManager,
        private ExigenceAvisMedical $exigenceAvisMedical,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        //support de GET seulement, avec en entrée uid et année
        $entity = $this->decisionAmenagementManager->parUidEtAnnee($uriVariables['uid'], (int) $uriVariables['annee']);

        if (null === $entity) {
            return null;
        }

        $decision = new DecisionAmenagementExamens($entity);
        $decision->dateAvisMedecinRequise = $this->exigenceAvisMedical->estRequisePour($entity);

        return $decision;
    }
}
