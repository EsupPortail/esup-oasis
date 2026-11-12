<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\Command\SignatureFastCircuitsCommand;
use App\Service\Signature\Fast\ClientFast;
use PHPUnit\Framework\TestCase;
use SoapClient;
use SoapFault;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SignatureFastCircuitsCommandTest extends TestCase
{
    public function testListsCircuitsOfSubscriber(): void
    {
        $soap = $this->createMock(SoapClient::class);
        $soap->method('__soapCall')->with('getCircuits', [['siren' => '123456789']])->willReturn((object) ['return' => [
            (object) ['circuitId' => 'ufr-sciences', 'circuitName' => 'UFR Sciences', 'circuitType' => 'SIGNATURE'],
        ]]);

        $commande = $this->commande($soap);
        $commande->execute([]);

        $commande->assertCommandIsSuccessful();
        $this->assertStringContainsString('ufr-sciences', $commande->getDisplay());
        $this->assertStringContainsString('UFR Sciences', $commande->getDisplay());
    }

    public function testConnectionFailureIsReported(): void
    {
        $soap = $this->createMock(SoapClient::class);
        $soap->method('__soapCall')->willThrowException(new SoapFault('HTTP', 'Could not connect to host'));

        $commande = $this->commande($soap);
        $commande->execute([]);

        $this->assertSame(Command::FAILURE, $commande->getStatusCode());
        $this->assertStringContainsString('Could not connect', $commande->getDisplay());
    }

    private function commande(SoapClient $soap): CommandTester
    {
        return new CommandTester(new SignatureFastCircuitsCommand(
            new ClientFast('https://parapheur.example/soap', '123456789', '/certificat.pem', null, null, $soap),
        ));
    }
}
