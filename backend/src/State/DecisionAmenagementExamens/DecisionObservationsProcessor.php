<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\State\DecisionAmenagementExamens;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\DecisionAmenagementExamens;
use App\ApiResource\Utilisateur;
use App\Message\RessourceModifieeMessage;
use App\Repository\DecisionAmenagementExamensRepository;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Enregistre les saisies libres de la décision (observations, date de l'avis du médecin),
 * sans toucher à son état.
 */
readonly class DecisionObservationsProcessor implements ProcessorInterface
{
    public function __construct(
        private DecisionAmenagementExamensRepository $decisionAmenagementExamensRepository,
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @param DecisionAmenagementExamens $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DecisionAmenagementExamens
    {
        $entity = $this->decisionAmenagementExamensRepository->find($data->id);
        $entity->setObservations($data->observations);
        $entity->setDateAvisMedecin($data->dateAvisMedecin);
        $this->decisionAmenagementExamensRepository->save($entity, true);

        // la fiche du bénéficiaire et la décision elle-même affichent ces saisies
        $this->messageBus->dispatch(new RessourceModifieeMessage(new Utilisateur($entity->getBeneficiaire())));
        $this->messageBus->dispatch(new RessourceModifieeMessage($data));

        return $data;
    }
}
