<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\Service\Signature\ParapheurDesactive;
use App\Service\Signature\ParapheurFactice;
use App\Service\Signature\ParapheurFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParapheurFactoryTest extends TestCase
{
    /**
     * @return array<string, array{?string}>
     */
    public static function variablesVidesProvider(): array
    {
        return [
            'absente' => [null],
            'vide' => [''],
            'espaces' => ['  '],
        ];
    }

    #[DataProvider('variablesVidesProvider')]
    public function testEmptyVariableGivesParapheurDesactive(?string $variable): void
    {
        $this->assertInstanceOf(ParapheurDesactive::class, $this->factory($variable)->create());
    }

    public function testVariableSelectsParapheurIgnoringCase(): void
    {
        $this->assertInstanceOf(ParapheurFactice::class, $this->factory('FACTICE')->create());
    }

    public function testUnknownParapheurThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory('inconnu')->create();
    }

    private function factory(?string $variable): ParapheurFactory
    {
        return new ParapheurFactory($variable, [new ParapheurDesactive(), new ParapheurFactice()]);
    }
}
