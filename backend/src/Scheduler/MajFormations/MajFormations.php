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

namespace App\Scheduler\MajFormations;

use App\Repository\FormationRepository;
use App\Service\SiScol\AbstractSiScolDataProvider;
use App\Service\SiScol\BackendUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: '1 hour', schedule: 'maj_formations')]
readonly class MajFormations
{
    public function __construct(private FormationRepository        $formationRepository,
                                private AbstractSiScolDataProvider $scolProvider,
                                private LoggerInterface            $logger)
    {

    }

    public function __invoke(): void
    {
        $nbMaj = 0;
        foreach ($this->formationRepository->incompletes() as $incomplete) {
            try {
                $data = $this->scolProvider->getFormation($incomplete);
            } catch (BackendUnavailableException) {
                $this->logger->warning('Backend scol indisponible - maj des formations abandonnée.');
                return;
            }
            $incomplete->setDiplome($data['diplome'])
                ->setNiveau($data['niveau'])
                ->setDiscipline($data['discipline']);
            $this->formationRepository->save($incomplete);
            $nbMaj++;
        }
        if ($nbMaj > 0) {
            /** @noinspection PhpUndefinedVariableInspection */
            $this->formationRepository->save($incomplete, true);
        }
        $this->logger->info($nbMaj . ' formations incomplètes mises à jour.');
    }

}