<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests\Service\SiScol;

use App\Service\SiScol\RedoublementCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RedoublementCalculatorTest extends TestCase
{
    public static function redoublementCases(): iterable
    {
        // Compteur natif nbr_ins_etp, sans cursus aménagé.
        yield 'première inscription à l\'étape → non redoublant' => [1, null, false];
        yield 'deuxième inscription à l\'étape → redoublant' => [2, null, true];

        // Garde cursus aménagé : neutralise le calcul même si nbr > 1.
        yield 'cursus aménagé (étalement) neutralise le redoublement' => [3, '1', false];
        yield 'cursus aménagé (contrat pluri-annuel) neutralise le redoublement' => [2, '2', false];

        // Données manquantes / limites.
        yield 'compteur null sans cursus → non redoublant' => [null, null, false];
        yield 'compteur 0 → non redoublant' => [0, null, false];
        yield 'cursus aménagé vide ignoré → redoublant' => [5, '', true];
        yield 'première inscription avec cursus aménagé → non redoublant' => [1, '1', false];
    }

    #[DataProvider('redoublementCases')]
    public function testEstRedoublant(
        ?int $nombreInscriptionsEtape,
        ?string $codeCursusAmenage,
        bool $expected,
    ): void {
        self::assertSame(
            $expected,
            (new RedoublementCalculator())->estRedoublant($nombreInscriptionsEtape, $codeCursusAmenage),
        );
    }
}
