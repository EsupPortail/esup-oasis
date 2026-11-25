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

namespace App\Tests;

use App\Serializer\Encoder\CustomCsvEncoder;
use PHPUnit\Framework\TestCase;

class CustomCsvEncoderTest extends TestCase
{
    /**
     * Vérifie que CustomCsvEncoder échappe les formules pour prévenir l'injection CSV.
     */
    public function testCustomCsvEncoderEscapesFormulas(): void
    {
        $encoder = new CustomCsvEncoder();
        $data = [
            ['nom' => '=cmd|"/C calc"!A0', 'prenom' => '+1234', 'valeur' => '@alert()'],
        ];

        $csv = $encoder->encode($data, 'customcsv');

        // Les valeurs commençant par =, +, -, @ doivent être préfixées d'un apostrophe ou échappées
        $this->assertStringNotContainsString(';=cmd', $csv);
        $this->assertStringNotContainsString(';+1234', $csv);
        $this->assertStringNotContainsString(';@alert', $csv);
    }
}
