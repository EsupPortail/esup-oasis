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

namespace App\State\Composante;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Composante;
use App\Entity\Utilisateur;
use App\Message\ModificationUtilisateurMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\ComposanteRepository;
use App\Service\ErreurLdapException;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class PatchComposanteProcessor implements ProcessorInterface
{

    public function __construct(private UtilisateurManager   $utilisateurManager,
                                private ComposanteRepository $composanteRepository,
                                private TransformerService   $transformerService,
                                private MessageBusInterface  $messageBus)
    {

    }

    /**
     * @param Composante $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return Composante
     * @throws ErreurLdapException
     * @throws ExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Composante
    {
        $entity = $this->composanteRepository->find($data->id);

        $nouveauxReferentsIds = array_map(fn($ref) => $ref->uid, $data->referents);
        $anciensReferentsIds = array_map(fn(utilisateur $ref) => $ref->getUid(), $entity->getReferents()->toArray());

        $referentsSupprimes = array_filter(
            $entity->getReferents()->toArray(),
            static fn(Utilisateur $referent) => !in_array($referent->getUid(), $nouveauxReferentsIds, true)
        );

        $referentsAjoutes = array_filter(
            $data->referents,
            static fn(\App\ApiResource\Utilisateur $referent) => !in_array($referent->uid, $anciensReferentsIds, true)
        );

        array_walk($referentsSupprimes,
            function (Utilisateur $referent) use ($entity) {
                $entity->removeReferent($referent);
                $this->messageBus->dispatch(new ModificationUtilisateurMessage($referent)); //on invalide le cache
                $utilisateurResource = $this->transformerService->transform($referent, \App\ApiResource\Utilisateur::class);
                $this->messageBus->dispatch(new RessourceModifieeMessage($utilisateurResource));
            }
        );

        array_walk($referentsAjoutes,
            function (\App\ApiResource\Utilisateur $ref) use ($entity) {
                $referent = $this->utilisateurManager->parUid($ref->uid, true);
                $entity->addReferent($referent);
                $this->messageBus->dispatch(new ModificationUtilisateurMessage($referent));
                $utilisateurResource = $this->transformerService->transform($referent, \App\ApiResource\Utilisateur::class);
                $this->messageBus->dispatch(new RessourceModifieeMessage($utilisateurResource));
            }
        );

        $this->composanteRepository->save($entity, true);


        $resource = $this->transformerService->transform($entity, Composante::class);
        $this->messageBus->dispatch(new RessourceModifieeMessage($resource));

        return $resource;
    }
}