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

namespace App\State\SportfHautNiveau;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\SportifHautNiveau;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\SportifHautNiveauRepository;
use App\State\TransformerService;
use Exception;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class SportifHautNiveauProcessor implements ProcessorInterface
{

    public function __construct(private SportifHautNiveauRepository $sportifHautNiveauRepository,
                                private TransformerService          $transformerService,
                                private MessageBusInterface         $messageBus)
    {

    }

    /**
     * @param SportifHautNiveau $data
     * @throws ExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?SportifHautNiveau
    {
        $entity = match ($data->id) {
            null => new \App\Entity\SportifHautNiveau(),
            default => $this->sportifHautNiveauRepository->find($data->id)
        };

        if ($operation instanceof Delete) {
            $this->sportifHautNiveauRepository->remove($entity, true);
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data));
            return null;
        }

        $entity->setNom($data->nom);
        $entity->setPrenom($data->prenom);
        $entity->setAnneeNaissance($data->anneeNaissance);
        $entity->setIdentifiantExterne($data->identifiantExterne);

        $this->sportifHautNiveauRepository->save($entity, true);

        $resource = $this->transformerService->transform($entity, SportifHautNiveau::class);
        if (null !== $data->id) {
            $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
        } else {
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));
        }

        return $resource;
    }
}