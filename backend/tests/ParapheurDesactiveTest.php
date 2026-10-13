<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\Service\Signature\ParapheurDesactive;
use LogicException;
use PHPUnit\Framework\TestCase;

class ParapheurDesactiveTest extends TestCase
{
    public function testIsNotDisponible(): void
    {
        self::assertFalse((new ParapheurDesactive())->estDisponible());
    }

    public function testDeposerThrows(): void
    {
        $this->expectException(LogicException::class);

        (new ParapheurDesactive())->deposer('%PDF', 'circuit', 'libellé', 'camille.aubert@univ.example');
    }

    public function testSuivreThrows(): void
    {
        $this->expectException(LogicException::class);

        (new ParapheurDesactive())->suivre('document');
    }

    public function testTelechargerThrows(): void
    {
        $this->expectException(LogicException::class);

        (new ParapheurDesactive())->telecharger('document');
    }
}
