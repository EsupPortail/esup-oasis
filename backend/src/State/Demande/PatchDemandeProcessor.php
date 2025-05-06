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

namespace App\State\Demande;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Demande;
use App\Message\RessourceModifieeMessage;
use App\State\TransformerService;
use Exception;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class PatchDemandeProcessor implements ProcessorInterface
{
    public function __construct(private DemandeManager      $demandeManager,
                                private Security            $security,
                                private TransformerService  $transformerService,
                                private MessageBusInterface $messageBus)
    {
    }

    /**
     * @param Demande $data
     * @throws Exception
     */
    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Demande
    {
        //soit changement d'état, soit simple commentaire
        $demande = $this->demandeManager->getDemande($data->id);
        if ($demande->getEtat()->getId() !== $data->etat->id) {
            $demande = $this->demandeManager->modifierDemande(
                demande: $demande,
                idEtat: $data->etat->id,
                commentaire: $data->commentaireChangementEtat,
                profilId: $data->profilAttribue?->id,
                user: $this->security->getUser()
            );
        } else {
            $this->demandeManager->ajouterCommentaire($demande, $data->commentaire);
        }

        $resource = $this->transformerService->transform($demande, Demande::class);

        $this->messageBus->dispatch(new RessourceModifieeMessage($resource));

        return $resource;
    }
}