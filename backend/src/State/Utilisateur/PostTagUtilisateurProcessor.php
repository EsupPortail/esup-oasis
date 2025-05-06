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

namespace App\State\Utilisateur;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\TagUtilisateur;
use App\ApiResource\Utilisateur;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\TagRepository;
use App\State\TransformerService;
use Exception;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class PostTagUtilisateurProcessor implements ProcessorInterface
{
    public function __construct(private UtilisateurManager  $utilisateurManager,
                                private TagRepository       $tagRepository,
                                private TransformerService  $transformerService,
                                private MessageBusInterface $messageBus)
    {
    }

    /**
     * @param TagUtilisateur $data
     * @throws ExceptionInterface
     */
    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TagUtilisateur
    {
        //POST uniquement
        try {
            $utilisateur = $this->utilisateurManager->parUid($uriVariables['uid']);
        } catch (Exception) {
            throw new NotFoundHttpException("Utilisateur inconnu");
        }

        $tag = $this->tagRepository->find($data->tag->id);

        try {
            $this->utilisateurManager->ajouterTag($utilisateur, $tag);
        } catch (UtilisateurNonBeneficiaireException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        $utilisateurResource = $this->transformerService->transform($utilisateur, Utilisateur::class);
        $data->uid = $utilisateur->getUid();
        //la ressource utilisateur contient la liste des Tags (!= TagUtilisateur) qui lui sont associés
        $this->messageBus->dispatch(new RessourceModifieeMessage($utilisateurResource));
        $this->messageBus->dispatch(new RessourceModifieeMessage($data));
        $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data));

        return $data;
    }
}