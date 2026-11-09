<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

namespace App\Tests;

use App\Service\Signature\RapportSuiviSignature;
use PHPUnit\Framework\TestCase;

class RapportSuiviSignatureTest extends TestCase
{
    public function testNewRapportCountsNothing(): void
    {
        $rapport = new RapportSuiviSignature();

        $this->assertSame(
            ['examinees' => 0, 'signees' => 0, 'refusees' => 0, 'closes' => 0, 'erreurs' => 0],
            $rapport->toArray(),
        );
    }

    public function testIncrementsAccumulateWithoutMutation(): void
    {
        $initial = new RapportSuiviSignature();

        $cumul = $initial
            ->avec(examinees: 1, signees: 1)
            ->avec(examinees: 1, refusees: 1)
            ->avec(examinees: 1, erreurs: 1);

        $this->assertSame(
            ['examinees' => 3, 'signees' => 1, 'refusees' => 1, 'closes' => 0, 'erreurs' => 1],
            $cumul->toArray(),
        );
        $this->assertSame(0, $initial->examinees);
    }

    public function testEmptyIncrementIsAccepted(): void
    {
        $rapport = new RapportSuiviSignature(examinees: 2);

        $this->assertSame(2, $rapport->avec(...[])->examinees);
    }
}
