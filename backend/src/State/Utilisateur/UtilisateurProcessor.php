<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\State\Utilisateur;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Utilisateur;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Service\ErreurLdapException;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;

/** @mago-ignore analysis:unused-property */
readonly class UtilisateurProcessor implements ProcessorInterface
{
    function __construct(
        private UtilisateurManager $utilisateurManager,
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @param Utilisateur $data
     * @param Operation   $operation
     * @param array       $uriVariables
     * @param array       $context
     * @return void
     * @throws ErreurLdapException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$operation instanceof Patch) {
            throw new RuntimeException('Erreur du dev, uniquement PATCH sur utilisateur');
        }

        $entity = $this->utilisateurManager->maj($data);
        //Si plus rattaché à un service, le rôle gestionnaire est perdu!
        $data->roles = $entity->getRoles();

        $this->messageBus->dispatch(new RessourceModifieeMessage($data));
        //Un patch ici peut impacter des collections (ajout/retrait de la liste des intervenants par ex.)
        $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data));

        return $data;
    }
}
