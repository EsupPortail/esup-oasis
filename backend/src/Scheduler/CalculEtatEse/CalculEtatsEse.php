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

namespace App\Scheduler\CalculEtatEse;

use App\Repository\UtilisateurRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: '1 day', from: '1:30', jitter: '300', schedule: 'calcul_etats_ese')]
readonly class CalculEtatsEse
{

    public function __construct(private UtilisateurRepository $utilisateurRepository,
                                private LoggerInterface       $logger)
    {

    }

    public function __invoke(): void
    {
        foreach ($this->utilisateurRepository->findAll() as $utilisateur) {
            $utilisateur->setEtatAvisEse($utilisateur->getEtatAvisEseCalcule());
            $this->utilisateurRepository->save($utilisateur);
        }

        $this->utilisateurRepository->save($utilisateur ?? null, true);

        $this->logger->info('Fin du traitement de maj des etats avis ese');
    }

}