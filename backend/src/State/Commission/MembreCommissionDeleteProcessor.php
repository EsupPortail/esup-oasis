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
use App\ApiResource\MembreCommission;
use App\Message\RessourceModifieeMessage;
use App\Repository\MembreCommissionRepository;
use Override;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class MembreCommissionDeleteProcessor implements ProcessorInterface
{

    public function __construct(private MembreCommissionRepository $membreCommissionRepository,
                                private MessageBusInterface        $messageBus)
    {
    }

    /**
     * @param MembreCommission $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return void
     * @throws ExceptionInterface
     */
    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->membreCommissionRepository->remove($this->membreCommissionRepository->find($data->id), true);
        $this->messageBus->dispatch(new RessourceModifieeMessage($data));
        $this->messageBus->dispatch(new RessourceModifieeMessage($data->utilisateur));
    }
}