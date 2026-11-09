<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests\Scheduler;

use App\Entity\Parametre;
use App\Entity\ValeurParametre;
use App\Repository\ParametreRepository;
use App\Scheduler\SuiviSignatures\SuiviSignaturesMessage;
use App\Scheduler\SuiviSignatures\SuiviSignaturesScheduler;
use App\Service\Signature\ParapheurDesactive;
use App\Service\Signature\ParapheurFactice;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Stringable;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\RecurringMessage;

final class SuiviSignaturesSchedulerTest extends TestCase
{
    public function testNothingScheduledWithoutParapheur(): void
    {
        $scheduler = new SuiviSignaturesScheduler(
            new ParapheurDesactive(),
            $this->parametres('15 minutes'),
            new ArrayAdapter(),
            new NullLogger(),
        );

        self::assertSame([], $scheduler->getSchedule()->getRecurringMessages());
    }

    public function testDefaultFrequencyIsOneHourWithoutParametre(): void
    {
        $passage = $this->seulPassage(new SuiviSignaturesScheduler(
            new ParapheurFactice(),
            $this->parametres(null),
            new ArrayAdapter(),
            new NullLogger(),
        ));

        self::assertStringContainsString('1 hour', (string) $passage->getTrigger());
    }

    public function testFrequencyComesFromParametre(): void
    {
        $passage = $this->seulPassage(new SuiviSignaturesScheduler(
            new ParapheurFactice(),
            $this->parametres('15 minutes'),
            new ArrayAdapter(),
            new NullLogger(),
        ));

        self::assertStringContainsString('15 minutes', (string) $passage->getTrigger());
    }

    public function testInvalidFrequencyFallsBackToDefault(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var string[] */
            public array $avertissements = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                if ('warning' === $level) {
                    $this->avertissements[] = (string) $message;
                }
            }
        };

        $passage = $this->seulPassage(new SuiviSignaturesScheduler(
            new ParapheurFactice(),
            $this->parametres('toutes les lunes'),
            new ArrayAdapter(),
            $logger,
        ));

        self::assertStringContainsString('1 hour', (string) $passage->getTrigger());
        self::assertCount(1, $logger->avertissements);
    }

    public function testPassageDispatchesSuiviMessage(): void
    {
        $passage = $this->seulPassage(new SuiviSignaturesScheduler(
            new ParapheurFactice(),
            $this->parametres(null),
            new ArrayAdapter(),
            new NullLogger(),
        ));

        $contexte = new MessageContext('suivi_signatures', 'id', $passage->getTrigger(), new DateTimeImmutable());
        $messages = iterator_to_array($passage->getMessages($contexte));

        self::assertCount(1, $messages);
        self::assertInstanceOf(SuiviSignaturesMessage::class, $messages[0]);
    }

    private function seulPassage(SuiviSignaturesScheduler $scheduler): RecurringMessage
    {
        $passages = $scheduler->getSchedule()->getRecurringMessages();
        self::assertCount(1, $passages);

        return array_values($passages)[0];
    }

    /** Dépôt de paramètres avec, ou sans, une valeur courante pour FREQUENCE_SUIVI_SIGNATURES. */
    private function parametres(?string $frequence): ParametreRepository
    {
        $parametre = null;
        if (null !== $frequence) {
            $valeur = new ValeurParametre();
            $valeur->setValeur($frequence);
            $valeur->setDebut(new DateTimeImmutable('-1 day'));

            $parametre = new Parametre();
            $parametre->setCle(Parametre::FREQUENCE_SUIVI_SIGNATURES);
            $parametre->addValeursParametre($valeur);
        }

        $repository = $this->createMock(ParametreRepository::class);
        $repository->method('findOneBy')->willReturn($parametre);

        return $repository;
    }
}
