<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\Entity\DecisionAmenagementExamens;
use App\Service\Signature\ParapheurFactice;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SignatureFacticeCommandTest extends KernelTestCase
{
    private ParapheurFactice $parapheur;
    private CommandTester $commande;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->parapheur = self::getContainer()->get(ParapheurFactice::class);
        $this->commande = new CommandTester(new Application(self::$kernel)->find('app:signature:factice'));
    }

    public function testListerShowsDepositedDocuments(): void
    {
        $documentId = $this->parapheur->deposer('%PDF', 'circuit-test', 'Décision - Camille Aubert', 'camille.aubert@univ.example');

        $this->commande->execute(['action' => 'lister']);

        $this->commande->assertCommandIsSuccessful();
        $this->assertStringContainsString($documentId, $this->commande->getDisplay());
        $this->assertStringContainsString('camille.aubert@univ.example', $this->commande->getDisplay());
    }

    public function testSignerSignsDocument(): void
    {
        $documentId = $this->parapheur->deposer('%PDF', 'circuit-test', 'Décision', 'camille.aubert@univ.example');

        $this->commande->execute(['action' => 'signer', 'document' => $documentId]);

        $this->commande->assertCommandIsSuccessful();
        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE, $this->parapheur->suivre($documentId)->etat);
        $this->assertNotNull($this->parapheur->suivre($documentId)->dateSignature);
    }

    public function testRefuserRefusesDocument(): void
    {
        $documentId = $this->parapheur->deposer('%PDF', 'circuit-test', 'Décision', 'camille.aubert@univ.example');

        $this->commande->execute(['action' => 'refuser', 'document' => $documentId]);

        $this->commande->assertCommandIsSuccessful();
        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE, $this->parapheur->suivre($documentId)->etat);
    }

    public function testUnknownDocumentFails(): void
    {
        $this->commande->execute(['action' => 'signer', 'document' => 'factice-inconnu']);

        $this->assertSame(Command::FAILURE, $this->commande->getStatusCode());
    }

    public function testActionWithoutDocumentIsInvalid(): void
    {
        $this->commande->execute(['action' => 'signer']);

        $this->assertSame(Command::INVALID, $this->commande->getStatusCode());
    }
}
