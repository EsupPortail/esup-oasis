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

namespace App\MessageHandler;

use App\Message\DemandeValideeMessage;
use App\Service\MailService;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: DemandeValideeMessage::class)]
readonly class DemandeValideeMessageHandler
{
    public function __construct(private MailService        $mailService,
                                private UtilisateurManager $utilisateurManager)
    {

    }


    public function __invoke(DemandeValideeMessage $message): void
    {
        //création du bénéficiaire
        $this->utilisateurManager->creerBeneficiairePourDemande(
            $message->getDemande(),
            $message->getIdProfil(),
            $message->getUidUtilisateur()
        );

        //Envoi d'un mail "Vous avez gagné!"
        $this->mailService->envoyerConfirmationDemandeValidee($message->getDemandeur(), $message->getTypeDemande());
    }
}