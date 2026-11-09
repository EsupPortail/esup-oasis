<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\ApiResource\PieceJointeBeneficiaire;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\Utilisateur;
use App\Message\RessourceCollectionModifieeMessage;
use App\Repository\PieceJointeBeneficiaireRepository;
use App\Service\Decision\ArchivageDecision;
use App\Service\FileStorage\StorageProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class ArchivageDecisionTest extends TestCase
{
    public function testArchivedDecisionRefreshesDocumentListOfBeneficiaire(): void
    {
        $beneficiaire = new Utilisateur();
        $beneficiaire->setUid('camille.aubert');
        $decision = new DecisionAmenagementExamens()->setBeneficiaire($beneficiaire);

        $storage = $this->createMock(StorageProviderInterface::class);
        $storage->method('store')->willReturn(['cheminComplet' => '/tmp/decision.pdf']);
        $messages = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $message) use (&$messages) {
            $messages[] = $message;

            return new Envelope($message);
        });

        new ArchivageDecision($this->createMock(PieceJointeBeneficiaireRepository::class), $storage, $bus)
            ->archiver($decision, '%PDF-test', new Utilisateur(), DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE);

        // la liste des documents du bénéficiaire est purgée du cache : la copie y apparaît aussitôt
        self::assertCount(1, $messages);
        self::assertInstanceOf(RessourceCollectionModifieeMessage::class, $messages[0]);
        $piece = $messages[0]->getResource();
        self::assertInstanceOf(PieceJointeBeneficiaire::class, $piece);
        self::assertSame('camille.aubert', $piece->uid);
    }
}
