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
use App\Service\Signature\Fast\EtatSignatureDeriver;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EtatSignatureDeriverTest extends TestCase
{
    /**
     * @return array<string, array{string[], string}>
     */
    public static function historiquesProvider(): array
    {
        return [
            'historique vide' => [
                [],
                DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE,
            ],
            'envoyé pour signature' => [
                ['Envoyé pour signature'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE,
            ],
            'signature suivie d\'une autre étape' => [
                ['Envoyé pour signature', 'Signé', 'Envoyé pour signature'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE,
            ],
            'circuit sans classement, terminé par la signature' => [
                ['Préparé', 'Envoyé pour visa', 'Visa approuvé', 'Envoyé pour visa', 'Visa approuvé', 'Envoyé pour signature', 'Signé'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE,
            ],
            'signature avec suffixe d\'étape en dernier' => [
                ['Envoyé pour signature', "Signé à l'étape 2"],
                DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE,
            ],
            'libellé inconnu ignoré' => [
                ['Envoyé pour signature', 'Nouveau libellé v3.9'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE,
            ],

            'classé' => [
                ['Envoyé pour signature', 'Signé', 'Signé', 'Classé'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE,
            ],
            'archivé' => [
                ['Envoyé pour signature', 'Signé', 'Archivé'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE,
            ],
            'classé malgré un échec technique antérieur' => [
                ["Échec de l'envoi à FAST", 'Envoyé pour signature', 'Signé', 'Classé'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE,
            ],

            'refusé avec suffixe d\'étape' => [
                ['Envoyé pour signature', "Refusé à l'étape OTP"],
                DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE,
            ],
            'signature rejetée' => [
                ['Envoyé pour signature', 'signature rejetée'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE,
            ],
            'visa désapprouvé' => [
                ['Envoyé pour visa', 'Visa désapprouvé'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE,
            ],
            'refus puis classement d\'interruption' => [
                ['Envoyé pour signature', 'Refusé', 'Classé (interrompu)'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE,
            ],

            'circuit interrompu' => [
                ['Envoyé pour signature', 'Classé (interrompu)'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_EXPIREE,
            ],
            'document remplacé après relance' => [
                ['Envoyé pour signature', 'Document remplacé'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_REMPLACEE,
            ],

            'échec de l\'envoi' => [
                ["Échec de l'envoi à FAST"],
                DecisionAmenagementExamens::ETAT_SIGNATURE_ERREUR,
            ],
            'échec du traitement' => [
                ['Envoyé pour signature', 'Échec du traitement FAST'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_ERREUR,
            ],

            'casse et accents fantaisistes' => [
                ['ENVOYÉ POUR SIGNATURE', 'CLASSE'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE,
            ],
            'refus sans accent' => [
                ['Refuse a l\'etape 2'],
                DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE,
            ],
        ];
    }

    /**
     * @param string[] $libelles
     */
    #[DataProvider('historiquesProvider')]
    public function testDeriveEtatFromHistorique(array $libelles, string $etatAttendu): void
    {
        $deriver = new EtatSignatureDeriver();

        $this->assertSame($etatAttendu, $deriver->deriver($libelles));
    }

    public function testEstablishmentCanWaitForArchiving(): void
    {
        $deriver = new EtatSignatureDeriver('Archivé');

        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE, $deriver->deriver(['Envoyé pour signature', 'Signé', 'Classé']));
        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE, $deriver->deriver(['Envoyé pour signature', 'Signé', 'Classé', 'Archivé']));
    }

    public function testFinalStateStaysFinalWhenFollowedByAnotherState(): void
    {
        // classé, puis archivé plus tard : le circuit reste terminé
        $deriver = new EtatSignatureDeriver('Classé');

        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE, $deriver->deriver(['Envoyé pour signature', 'Signé', 'Classé', 'Archivé']));
    }

    public function testVisaOnlyCircuitEndsOnLastVisa(): void
    {
        $deriver = new EtatSignatureDeriver('Visa approuvé');
        $historique = [
            ['stateName' => 'Envoyé pour visa', 'date' => '2026-09-01T09:00:00+02:00'],
            ['stateName' => 'Visa approuvé', 'date' => '2026-09-01T10:00:00+02:00'],
            ['stateName' => 'Envoyé pour visa', 'date' => '2026-09-01T10:00:00+02:00'],
        ];

        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE, $deriver->deriver(array_column($historique, 'stateName')));

        $historique[] = ['stateName' => 'Visa approuvé', 'date' => '2026-09-02T11:00:00+02:00'];

        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE, $deriver->deriver(array_column($historique, 'stateName')));
        $this->assertSame('2026-09-02 11:00', $deriver->dateDeSignature($historique)?->format('Y-m-d H:i'));
    }

    public function testDateSignatureIsLastSignature(): void
    {
        $date = new EtatSignatureDeriver()->dateDeSignature([
            ['stateName' => 'Envoyé pour signature', 'date' => '2026-09-01T09:00:00+02:00'],
            ['stateName' => 'Signé', 'date' => '2026-09-02T10:00:00+02:00'],
            ['stateName' => 'Visa approuvé', 'date' => '2026-09-03T11:00:00+02:00'],
            ['stateName' => 'Signé', 'date' => '2026-09-04T16:30:00+02:00'],
            ['stateName' => 'Classé', 'date' => '2026-09-04T16:31:00+02:00'],
        ]);

        $this->assertEquals(new DateTimeImmutable('2026-09-04T16:30:00+02:00'), $date);
    }

    public function testDateSignatureFallsBackToClassement(): void
    {
        $date = new EtatSignatureDeriver()->dateDeSignature([
            ['stateName' => 'Envoyé pour signature', 'date' => '2026-09-01T09:00:00+02:00'],
            ['stateName' => 'Visa approuvé', 'date' => '2026-09-02T10:00:00+02:00'],
            ['stateName' => 'CLASSE', 'date' => '2026-09-02T10:01:00+02:00'],
        ]);

        $this->assertEquals(new DateTimeImmutable('2026-09-02T10:01:00+02:00'), $date);
    }

    public function testUnreadableDateGivesNoDateSignature(): void
    {
        $deriver = new EtatSignatureDeriver();

        $this->assertNull($deriver->dateDeSignature([
            ['stateName' => 'Signé', 'date' => 'pas une date'],
        ]));
        $this->assertNull($deriver->dateDeSignature([
            ['stateName' => 'Envoyé pour signature', 'date' => '2026-09-01T09:00:00+02:00'],
        ]));
        $this->assertNull($deriver->dateDeSignature([]));
    }

    public function testSignatureWithEtapeSuffixIsCounted(): void
    {
        $date = new EtatSignatureDeriver()->dateDeSignature([
            ['stateName' => "Signé à l'étape 2", 'date' => '2026-09-04T16:30:00+02:00'],
            ['stateName' => 'Signature rejetée', 'date' => '2026-09-05T08:00:00+02:00'],
            ['stateName' => 'Classé', 'date' => '2026-09-05T08:01:00+02:00'],
        ]);

        $this->assertEquals(new DateTimeImmutable('2026-09-04T16:30:00+02:00'), $date);
    }
}
