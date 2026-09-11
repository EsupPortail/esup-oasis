<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests\Service\SiScol;

use App\Service\SiScol\NiveauExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NiveauExtractorTest extends TestCase
{
    public static function codeEtapeCases(): iterable
    {
        yield 'licence 1' => ['L1INFO', 'L1'];
        yield 'licence 2' => ['L2MATHS', 'L2'];
        yield 'licence 3' => ['L3DROIT', 'L3'];
        yield 'master 1' => ['M1ARTS', 'M1'];
        yield 'master 2' => ['M2POLI', 'M2'];
        yield 'doctorat 1' => ['D1PHYS', 'D1'];
        yield 'lower-case input is normalized to upper-case' => ['l1info', 'L1'];

        yield 'no level prefix → null (PASS-MED)' => ['PASS-MED', null];
        yield 'no level prefix → null (LAS)' => ['LAS-CHIMIE', null];
        yield 'unknown level (L4) → null' => ['L4WHAT', null];
        yield 'unknown level (M3) → null' => ['M3OUT', null];
        yield 'unknown level (D4) → null' => ['D4OOPS', null];
        yield 'empty string → null' => ['', null];
        yield 'null input → null' => [null, null];
    }

    #[DataProvider('codeEtapeCases')]
    public function testExtract(?string $codeEtape, ?string $expected): void
    {
        self::assertSame($expected, (new NiveauExtractor())->extract($codeEtape));
    }
}
