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

namespace App\State\Commission;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\MembreCommission;
use App\Entity\Utilisateur;
use App\Message\ModificationUtilisateurMessage;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\CommissionRepository;
use App\Repository\MembreCommissionRepository;
use App\Service\ErreurLdapException;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use Override;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class MembreCommissionPutProcessor implements ProcessorInterface
{

    public function __construct(private MembreCommissionRepository $membreCommissionRepository,
                                private UtilisateurManager         $utilisateurManager,
                                private CommissionRepository       $commissionRepository,
                                private TransformerService         $transformerService,
                                private MessageBusInterface        $messageBus)
    {
    }

    /**
     * @param MembreCommission $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return MembreCommission
     * @throws ErreurLdapException
     */
    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MembreCommission
    {
        $entity = match ($data->id) {
            null => new \App\Entity\MembreCommission(),
            default => $this->membreCommissionRepository->find($data->id)
        };

        $entity->setCommission($this->commissionRepository->find($uriVariables['commissionId']));
        $utilisateur = $this->utilisateurManager->parUid($uriVariables['uid'], true);
        $entity->setUtilisateur($utilisateur);
        $entity->setRoles(
            match (true) {
                $utilisateur->isGestionnaire() || $utilisateur->isAdmin() => [
                    Utilisateur::ROLE_MEMBRE_COMMISSION,
                    Utilisateur::ROLE_ATTRIBUER_PROFIL,
                    Utilisateur::ROLE_VALIDER_CONFORMITE_DEMANDE,
                ],
                default => array_unique($data->roles)
            }
        );

        $this->membreCommissionRepository->save($entity, true);

        $this->messageBus->dispatch(new ModificationUtilisateurMessage($utilisateur));

        $resource = $this->transformerService->transform($entity, MembreCommission::class);
        if (null !== $data->id) {
            $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
        } else {
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));
        }
        return $resource;
    }
}