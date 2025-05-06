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
use App\Entity\ProfilBeneficiaire;
use App\Message\DemandeReceptionneeMessage;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use App\Service\MailService;
use App\State\Demande\DemandeManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: DemandeReceptionneeMessage::class)]
readonly class DemandeReceptionneeMessageHandler
{
    public function __construct(private MailService        $mailService,
                                private DemandeManager     $demandeManager,
                                private ReponseRepository  $reponseRepository,
                                private QuestionRepository $questionRepository,
    )
    {

    }

    public function __invoke(DemandeReceptionneeMessage $message): void
    {
        //Envoi d'un mail "bien reçu, on vous tient au jus"
        $this->mailService->envoyerConfirmationDemandeReceptionnee($message->getDemandeur(), $message->getTypeDemande());

        //Si numéro PSQS, on attribue automatiquement le profil sportif HN
        $demande = $message->getDemande();
        $questionPSQS = $this->questionRepository->findOneBy([
            'tableOptions' => 'sportif_haut_niveau',
        ]);
        $reponsePSQS = $this->reponseRepository->findBy(
            [
                'repondant' => $demande->getDemandeur(),
                'question' => $questionPSQS,
                'campagne' => $demande->getCampagne(),
            ]
        );
        if (!empty($reponsePSQS)) {
            //une réponse à cette question est forcément validée en amont et donne ls statut sportif haut niveau
            $this->demandeManager->modifierDemande(
                demande: $demande,
                idEtat: EtatDemande::PROFIL_VALIDE,
                commentaire: "Numéro PSQS valide, attribution automatique du profil sportif haut niveau",
                profilId: ProfilBeneficiaire::SPORTIF_HAUT_NIVEAU,
                user: $message->getUidUtilisateur()
            );
        }
    }

}