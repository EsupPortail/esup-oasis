<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Scheduler\SuiviSignatures;

use App\Entity\Parametre;
use App\Repository\ParametreRepository;
use App\Service\Signature\AbstractParapheur;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Exception\InvalidArgumentException;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Fréquence réglée par le paramètre FREQUENCE_SUIVI_SIGNATURES (« 15 minutes », « 1 hour »…),
 * une heure par défaut.
 */
#[AsSchedule('suivi_signatures')]
readonly class SuiviSignaturesScheduler implements ScheduleProviderInterface
{
    public const string FREQUENCE_PAR_DEFAUT = '1 hour';

    public function __construct(
        private AbstractParapheur $parapheur,
        private ParametreRepository $parametreRepository,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {}

    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        // dépend du parapheur et non des circuits : vider les circuits ne doit pas
        // abandonner le suivi des décisions déjà déposées
        if (!$this->parapheur->estDisponible()) {
            return $schedule;
        }

        return $schedule
            ->stateful($this->cache) // un passage manqué pendant un arrêt est rattrapé
            ->processOnlyLastMissedRun(true) // une seule fois, pas autant que de passages manqués
            ->add($this->passageRecurrent());
    }

    private function passageRecurrent(): RecurringMessage
    {
        $parametre = $this->parametreRepository->findOneBy(['cle' => Parametre::FREQUENCE_SUIVI_SIGNATURES]);
        $frequence = trim((string) $parametre?->getValeurCourante()?->getValeur());
        if ('' !== $frequence) {
            try {
                return RecurringMessage::every($frequence, new SuiviSignaturesMessage());
            } catch (InvalidArgumentException $e) {
                // Tous les traitements planifiés partagent le même processus de worker : une
                // fréquence mal saisie ne doit pas les empêcher de démarrer.
                $this->logger->warning(
                    'Paramètre FREQUENCE_SUIVI_SIGNATURES invalide ({frequence}) : suivi des signatures planifié toutes les {defaut}.',
                    ['frequence' => $frequence, 'defaut' => self::FREQUENCE_PAR_DEFAUT, 'erreur' => $e->getMessage()],
                );
            }
        }

        return RecurringMessage::every(self::FREQUENCE_PAR_DEFAUT, new SuiviSignaturesMessage());
    }
}
