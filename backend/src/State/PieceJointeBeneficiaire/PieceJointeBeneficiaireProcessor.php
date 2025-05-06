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

namespace App\State\PieceJointeBeneficiaire;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\PieceJointeBeneficiaire;
use App\Message\RessourceCollectionModifieeMessage;
use App\Repository\FichierRepository;
use App\Repository\PieceJointeBeneficiaireRepository;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class PieceJointeBeneficiaireProcessor implements ProcessorInterface
{

    use ClockAwareTrait;

    public function __construct(private readonly PieceJointeBeneficiaireRepository $repository,
                                private readonly UtilisateurManager                $utilisateurManager,
                                private readonly FichierRepository                 $fichierRepository,
                                private readonly Security                          $security,
                                private readonly TransformerService                $transformerService,
                                private readonly MessageBusInterface               $messageBus)
    {

    }


    /**
     * POST et DELETE uniquement
     *
     * @param PieceJointeBeneficiaire $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return void
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?PieceJointeBeneficiaire
    {
        if ($operation instanceof Delete) {
            $entity = $this->repository->find($data->id);
            $this->repository->remove($entity, true);
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data));
            return null;
        }

        //création
        $entity = new \App\Entity\PieceJointeBeneficiaire();
        $entity->setLibelle($data->libelle);
        $entity->setDateDepot($this->now());
        $entity->setBeneficiaire($this->utilisateurManager->parUid($uriVariables['uid']));
        $entity->setFichier($this->fichierRepository->find($data->fichier->id));
        $entity->setUtilisateurCreation($this->security->getUser());

        $this->repository->save($entity, true);

        $resource = $this->transformerService->transform($entity, PieceJointeBeneficiaire::class);

        $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));

        return $resource;

    }
}