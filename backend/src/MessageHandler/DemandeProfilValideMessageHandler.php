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
use App\Message\DemandeProfilValideMessage;
use App\Repository\ProfilBeneficiaireRepository;
use App\State\Demande\DemandeManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: DemandeProfilValideMessage::class)]
readonly class DemandeProfilValideMessageHandler
{
    public function __construct(private DemandeManager               $demandeManager,
                                private ProfilBeneficiaireRepository $profilBeneficiaireRepository)
    {

    }

    public function __invoke(DemandeProfilValideMessage $message): void
    {
        /**
         * Si charte liée, création + ATTENTE_VALIDATION_CHARTE
         */
        $demande = $message->getDemande();
        $profil = $this->profilBeneficiaireRepository->find($message->getIdProfil());
        if ($profil->getChartes()->count() > 0) {
            $this->demandeManager->ajouterChartes($demande);
            $this->demandeManager->modifierDemande(
                demande: $demande,
                idEtat: EtatDemande::ATTENTE_VALIDATION_CHARTE,
                commentaire: $message->getCommentaire(),
                profilId: $message->getIdProfil(),
                user: $message->getUidUtilisateur()
            );
        } else {
            $avecAccompagnement = $this->demandeManager->demandeAvecAccompagnement($demande);
            if ($avecAccompagnement) {
                //on passe à l'état "attente de validation accompagnement"
                $this->demandeManager->modifierDemande(
                    demande: $demande,
                    idEtat: EtatDemande::ATTENTE_VALIDATION_ACCOMPAGNEMENT,
                    commentaire: $message->getCommentaire(),
                    profilId: $message->getIdProfil(),
                    user: $message->getUidUtilisateur()
                );
            } else {
                $this->demandeManager->modifierDemande(
                    demande: $demande,
                    idEtat: EtatDemande::VALIDEE,
                    commentaire: $message->getCommentaire(),
                    profilId: $message->getIdProfil(),
                    user: $message->getUidUtilisateur()
                );
            }
        }
    }

}