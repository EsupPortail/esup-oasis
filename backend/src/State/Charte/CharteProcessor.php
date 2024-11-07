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

namespace App\State\Charte;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Charte;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\CharteRepository;
use App\Repository\ProfilBeneficiaireRepository;
use App\State\TransformerService;
use Override;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class CharteProcessor implements ProcessorInterface
{
    public function __construct(private CharteRepository             $charteRepository,
                                private ProfilBeneficiaireRepository $profilBeneficiaireRepository,
                                private TransformerService           $transformerService,
                                private MessageBusInterface          $messageBus)
    {
    }

    /**
     * @param Charte    $data
     * @param Operation $operation
     * @param array     $uriVariables
     * @param array     $context
     * @return Charte
     */
    #[Override] public function process($data, Operation $operation, array $uriVariables = [], array $context = []): Charte
    {
        $entity = match ($data->id) {
            null => new \App\Entity\Charte(),
            default => $this->charteRepository->find($data->id)
        };

        $entity->setContenu($data->contenu);
        $entity->setLibelle($data->libelle);

        //suppression profils non présents
        foreach ($entity->getProfilsAssocies() as $existant) {
            foreach ($data->profilsAssocies as $profil) {
                if ($existant->getId() === $profil->id) {
                    continue 2;
                }
            }
            $entity->removeProfilsAssocy($existant);
        }
        //ajout profils manquants
        foreach ($data->profilsAssocies as $profil) {
            $entity->addProfilsAssocy($this->profilBeneficiaireRepository->find($profil->id));
        }

        $this->charteRepository->save($entity, true);

        $resource = $this->transformerService->transform($entity, Charte::class);

        if (null !== $data->id) {
            $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
        } else {
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));
        }

        return $resource;
    }
}