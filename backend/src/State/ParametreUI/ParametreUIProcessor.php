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

namespace App\State\ParametreUI;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ParametreUI;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\ParametreUIRepository;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Component\Messenger\MessageBusInterface;

class ParametreUIProcessor implements ProcessorInterface
{

    public function __construct(
        private readonly ParametreUIRepository $parametreUIRepository,
        private readonly UtilisateurManager    $utilisateurManager,
        private readonly TransformerService    $transformerService,
        private readonly MessageBusInterface   $messageBus)
    {

    }

    /**
     * @param ParametreUI $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return void
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $utilisateur = $this->utilisateurManager->parUid($uriVariables['uid']);
        $param = $this->parametreUIRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'cle' => $uriVariables['cle'],
        ]);

        $param = match ($param) {
            null => new \App\Entity\ParametreUI(),
            default => $param
        };

        //DELETE
        if ($operation instanceof Delete) {
            $this->parametreUIRepository->remove($param, true);
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data));
            return;
        }

        //PUT
        $param->setCle($uriVariables['cle']);
        $param->setUtilisateur($utilisateur);
        $param->setValeur($data->valeur);

        $this->parametreUIRepository->save($param, true);

        $resource = $this->transformerService->transform($param, ParametreUI::class);

        $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
        $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));

        return $resource;
    }
}