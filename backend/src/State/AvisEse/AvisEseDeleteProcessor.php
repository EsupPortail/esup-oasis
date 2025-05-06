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

namespace App\State\AvisEse;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Message\AvisEseModifieMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\AvisEseRepository;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class AvisEseDeleteProcessor implements ProcessorInterface
{

    public function __construct(private AvisEseRepository   $avisEseRepository,
                                private MessageBusInterface $messageBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $entity = $this->avisEseRepository->find($data->id);
        $this->avisEseRepository->remove($entity, true);

        $this->messageBus->dispatch(new RessourceModifieeMessage($data));
        $this->messageBus->dispatch(new AvisEseModifieMessage($entity));
    }
}