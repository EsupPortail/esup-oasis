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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Commission;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\CommissionRepository;
use App\State\TransformerService;
use Override;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class CommissionProcessor implements ProcessorInterface
{

    public function __construct(private CommissionRepository $commissionRepository,
                                private TransformerService   $transformerService,
                                private MessageBusInterface  $messageBus)
    {

    }

    /**
     * @param Commission $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return Commission
     * @throws ExceptionInterface
     */
    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Commission
    {
        if (null == $data->id) {
            $entity = new \App\Entity\Commission();
        } else {
            $entity = $this->commissionRepository->find($data->id);
        }

        $entity->setActif($data->actif);
        $entity->setLibelle($data->libelle);

        $this->commissionRepository->save($entity, true);

        $resource = $this->transformerService->transform($entity, Commission::class);
        if (null !== $data->id) {
            $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
        } else {
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));
        }
        return $resource;
    }
}