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

use App\Entity\EtatDemande;
use App\Message\CharteValideeMessage;
use App\State\Demande\DemandeManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: CharteValideeMessage::class)]
readonly class CharteValideeMessageHandler
{
    public function __construct(private DemandeManager $demandeManager)
    {

    }


    public function __invoke(CharteValideeMessage $message): void
    {
        /**
         * On vérifie si toutes les chartes sont validées, si oui, on passe à l'état ATTENTE_VALIDATION_ACCOMPAGNEMENT
         */
        $demande = $message->getCharte()->getDemande();
        foreach ($demande->getChartes() as $charte) {
            if (!$charte->estValidee()) {
                return;
            }
        }

        $this->demandeManager->modifierDemande(
            demande: $demande,
            idEtat: EtatDemande::ATTENTE_VALIDATION_ACCOMPAGNEMENT,
            commentaire: 'Chartes validées',
            user: $demande->getDemandeur()
        );
    }

}