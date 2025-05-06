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

namespace App\State\PieceJustificative;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Telechargement;
use App\Entity\Fichier;
use App\Message\RessourceCollectionModifieeMessage;
use App\Repository\FichierRepository;
use App\Service\FileStorage\StorageProviderInterface;
use App\State\TransformerService;
use Exception;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class TelechargementProcessor implements ProcessorInterface
{
    public function __construct(private StorageProviderInterface $storageProvider,
                                private FichierRepository        $fichierRepository,
                                private Security                 $security,
                                private TransformerService       $transformerService,
                                private MessageBusInterface      $messageBus)
    {

    }


    /**
     * @param Telechargement $data
     * @throws Exception
     */
    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Telechargement
    {
        //On envoie le fichier sur le stockage
        $metadata = $this->storageProvider->copy($data->file);

        //on crée la référence du fichier en base
        $fichier = new Fichier();
        $fichier->setNom($data->file->getClientOriginalName());
        $fichier->setTypeMime($data->file->getClientMimeType());
        $fichier->setProprietaire($this->security->getUser());
        $fichier->setMetadata($metadata);

        $this->fichierRepository->save($fichier, true);

        $resource = $this->transformerService->transform($fichier, Telechargement::class);

        $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));

        return $resource;
    }
}