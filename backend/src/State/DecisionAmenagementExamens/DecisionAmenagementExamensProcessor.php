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
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\DecisionAmenagementExamens;
use App\Entity\Utilisateur;
use App\Message\DecisionEditionDemandeeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\DecisionAmenagementExamensRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class DecisionAmenagementExamensProcessor implements ProcessorInterface
{
    public function __construct(private DecisionAmenagementExamensRepository $decisionAmenagementExamensRepository,
                                private Security                             $security,
                                private MessageBusInterface                  $messageBus)
    {

    }

    /**
     * @param DecisionAmenagementExamens $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return void
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        //PATCH seulement
        $entity = $this->decisionAmenagementExamensRepository->find($data->id);
        $entity->setEtat($data->etat);
        $this->decisionAmenagementExamensRepository->save($entity, true);

        //on envoie un message de MAJ pour traitement async
        if ($data->etat === \App\Entity\DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE) {
            $user = $this->security->getUser();
            assert($user instanceof Utilisateur);

            $this->messageBus->dispatch(new DecisionEditionDemandeeMessage($entity->getId(), $user->getUid()));
        }

        $this->messageBus->dispatch(new RessourceModifieeMessage($data));

        return $data;
    }
}