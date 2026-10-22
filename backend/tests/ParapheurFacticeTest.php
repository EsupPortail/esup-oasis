<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

namespace App\Tests;

use App\Entity\DecisionAmenagementExamens;
use App\Service\Signature\DocumentInconnuException;
use App\Service\Signature\ParapheurFactice;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ParapheurFacticeTest extends TestCase
{
    private const string DESTINATAIRE = 'camille.aubert@univ.example';

    private ?string $repertoire = null;

    protected function tearDown(): void
    {
        if (null !== $this->repertoire && is_dir($this->repertoire)) {
            array_map(unlink(...), glob($this->repertoire . '/*') ?: []);
            rmdir($this->repertoire);
        }
    }

    public function testDeposedDocumentIsEnSignature(): void
    {
        $parapheur = new ParapheurFactice();

        $documentId = $parapheur->deposer('%PDF-factice', 'circuit-test', 'Décision - Camille Aubert', self::DESTINATAIRE);

        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE, $parapheur->suivre($documentId)->etat);
        $this->assertNull($parapheur->suivre($documentId)->dateSignature);
        $this->assertSame(self::DESTINATAIRE, $parapheur->documents()[$documentId]['destinataire']);
    }

    public function testSignedDocumentHasDateSignature(): void
    {
        $parapheur = new ParapheurFactice();
        $documentId = $parapheur->deposer('%PDF-factice', 'circuit-test', 'Décision', self::DESTINATAIRE);
        $date = new DateTimeImmutable('2026-09-04T16:30:00+02:00');

        $parapheur->marquerSignee($documentId, $date);

        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE, $parapheur->suivre($documentId)->etat);
        $this->assertEquals($date, $parapheur->suivre($documentId)->dateSignature);
    }

    public function testRefusedDocumentIsRefusee(): void
    {
        $parapheur = new ParapheurFactice();
        $documentId = $parapheur->deposer('%PDF-factice', 'circuit-test', 'Décision - Théo Marchand', self::DESTINATAIRE);

        $parapheur->marquerRefusee($documentId);

        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE, $parapheur->suivre($documentId)->etat);
    }

    public function testTwoDepositsKeepTheirOwnDocument(): void
    {
        $parapheur = new ParapheurFactice();

        $premier = $parapheur->deposer('%PDF-un', 'circuit-test', 'Décision - un', self::DESTINATAIRE);
        $second = $parapheur->deposer('%PDF-deux', 'circuit-test', 'Décision - deux', self::DESTINATAIRE);

        $this->assertNotSame($premier, $second);
        $this->assertSame('%PDF-un', $parapheur->telecharger($premier));
        $this->assertSame('%PDF-deux', $parapheur->telecharger($second));
    }

    public function testDocumentsAreSharedThroughDirectory(): void
    {
        // le dépôt (worker), la signature (commande) et le suivi (planificateur) sont trois processus
        $depot = new ParapheurFactice($this->repertoire());
        $documentId = $depot->deposer("%PDF-\x00binaire", 'circuit-test', 'Décision', self::DESTINATAIRE);

        new ParapheurFactice($this->repertoire())->marquerSignee($documentId, new DateTimeImmutable('2026-09-04T16:30:00+02:00'));

        $suivi = new ParapheurFactice($this->repertoire());
        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE, $suivi->suivre($documentId)->etat);
        $this->assertEquals(new DateTimeImmutable('2026-09-04T16:30:00+02:00'), $suivi->suivre($documentId)->dateSignature);
        $this->assertSame("%PDF-\x00binaire", $suivi->telecharger($documentId));
        $this->assertSame([$documentId], array_keys($suivi->documents()));
    }

    public function testDocumentIdOutsideDirectoryIsUnknown(): void
    {
        $parapheur = new ParapheurFactice($this->repertoire());

        $this->expectException(DocumentInconnuException::class);
        $parapheur->suivre('../../config/secrets');
    }

    public function testUnknownDocumentThrows(): void
    {
        $parapheur = new ParapheurFactice();

        $this->expectException(DocumentInconnuException::class);
        $parapheur->suivre('document-jamais-depose');
    }

    private function repertoire(): string
    {
        return $this->repertoire ??= sys_get_temp_dir() . '/' . uniqid('parapheur-factice-test-', true);
    }
}
