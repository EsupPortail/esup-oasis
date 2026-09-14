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

namespace App\Scheduler\Nettoyage;

use App\Entity\EtatDemande;
use App\State\Demande\DemandeManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: '1 hour', jitter: '300', schedule: 'nettoyage')]
readonly class NettoyerDemandesObsoletes
{
    public function __construct(
        private DemandeManager $demandeManager,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): void
    {
        $this->logger->info('Début du traitement des demandes obsolètes');
        $obsoletes = $this->demandeManager->demandesObsoletes();
        foreach ($obsoletes as $demande) {
            $this->demandeManager->modifierDemande(
                $demande,
                EtatDemande::REFUSEE,
                "Cette campagne de demande est désormais fermée. 
                Si vous souhaitez toujours être accompagné(e), veuillez renouveler 
                votre demande à l'ouverture de la prochaine campagne ou prendre 
                contact avec votre référent.",
                user: $demande->getDemandeur(), // refus auto = refus par le demandeur lui-même !
            );
        }
        $this->logger->info(count($obsoletes) . " demandes obsolètes passées à l'état refusé.");
    }
}
