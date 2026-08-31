<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Amenagement;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class AmenagementActifTest extends TestCase
{
    private function amenagement(?string $start, ?string $end, string $now): Amenagement
    {
        $amenagement = new Amenagement();
        $amenagement->setDebut($start === null ? null : new DateTimeImmutable($start));
        $amenagement->setFin($end === null ? null : new DateTimeImmutable($end));
        $amenagement->setClock(new MockClock(new DateTimeImmutable($now)));

        return $amenagement;
    }

    public function testActiveThroughItsLastDay(): void
    {
        // debut et fin sont des colonnes date : elles hydratent à minuit. Une
        // fin affichée « au 31 août 2026 » est inclusive : l'aménagement doit
        // rester actif toute cette journée au lieu d'expirer à 00:00.
        self::assertTrue($this->amenagement('2025-09-01', '2026-08-31', '2026-08-31 11:00:00')->isActif());
    }

    public function testInactiveDayAfterEnd(): void
    {
        self::assertFalse($this->amenagement('2025-09-01', '2026-08-31', '2026-09-01 00:00:01')->isActif());
    }

    public function testActiveFromFirstDay(): void
    {
        self::assertTrue($this->amenagement('2026-08-31', '2027-08-31', '2026-08-31 11:00:00')->isActif());
    }

    public function testInactiveBeforeStart(): void
    {
        self::assertFalse($this->amenagement('2026-09-01', '2027-08-31', '2026-08-31 23:59:59')->isActif());
    }

    public function testOpenEndedStaysActive(): void
    {
        self::assertTrue($this->amenagement('2026-08-25', null, '2026-08-31 11:00:00')->isActif());
    }

    public function testOpenStartedStaysActive(): void
    {
        // debut nullable : en PHP null <= $objet vaut vrai, l'aménagement est
        // donc actif depuis toujours. Comportement inchangé par le fix, figé
        // ici explicitement (symétrique de la fin ouverte).
        self::assertTrue($this->amenagement(null, '2027-08-31', '2026-08-31 11:00:00')->isActif());
    }
}
