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
use App\ApiResource\AvisEse;
use App\Message\AvisEseModifieMessage;
use App\Message\RessourceCollectionModifieeMessage;
use App\Repository\AvisEseRepository;
use App\Repository\FichierRepository;
use App\Service\ErreurLdapException;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class AvisEsePostProcessor implements ProcessorInterface
{

    public function __construct(private FichierRepository   $fichierRepository,
                                private AvisEseRepository   $avisEseRepository,
                                private UtilisateurManager  $utilisateurManager,
                                private TransformerService  $transformerService,
                                private MessageBusInterface $messageBus)
    {

    }

    /**
     * @param AvisEse $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return AvisEse
     * @throws ErreurLdapException
     * @throws ExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AvisEse
    {
        $entity = new \App\Entity\AvisEse();
        $entity->setLibelle($data->libelle);
        $entity->setCommentaire($data->commentaire);
        $entity->setDebut($data->debut);
        $entity->setFin($data->fin);
        $entity->setFichier(match ($data->fichier) {
            null => null,
            default => $this->fichierRepository->find($data->fichier->id)
        });
        $entity->setUtilisateur($this->utilisateurManager->parUid($uriVariables['uid']));

        $this->avisEseRepository->save($entity, true);

        $this->messageBus->dispatch(new AvisEseModifieMessage($entity));

        $resource = $this->transformerService->transform($entity, AvisEse::class);

        $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));

        return $resource;
    }
}