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

namespace App\State\Entretien;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Entretien;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\EntretienRepository;
use App\Repository\FichierRepository;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class EntretienProcessor implements ProcessorInterface
{
    public function __construct(private EntretienRepository $entretienRepository,
                                private TransformerService  $transformerService,
                                private UtilisateurManager  $utilisateurManager,
                                private FichierRepository   $fichierRepository,
                                private Security            $security,
                                private MessageBusInterface $messageBus)
    {
    }

    /**
     * @param Entretien $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return void
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Entretien
    {
        $entity = match ($data->id) {
            null => new \App\Entity\Entretien(),
            default => $this->entretienRepository->find($data->id)
        };

        //DELETE
        if ($operation instanceof Delete) {
            $this->entretienRepository->remove($entity, true);
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data));
            return null;
        }

        //POST
        if (null == $entity->getUtilisateur()) {
            $entity->setUtilisateur($this->utilisateurManager->parUid($uriVariables['uid']));
        }

        //POST/PATCH
        $entity->setDate($data->date);
        $entity->setFichier(match ($data->fichier) {
            null => null,
            default => $this->fichierRepository->find($data->fichier->id)
        });
        $entity->setCommentaire($data->commentaire);
        $entity->setGestionnaire($this->security->getUser());

        $this->entretienRepository->save($entity, true);

        $resource = $this->transformerService->transform($entity, Entretien::class);

        if (null !== $data->id) {
            $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
        } else {
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));
        }

        return $resource;
    }
}