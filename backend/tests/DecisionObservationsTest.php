<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\Entity\DecisionAmenagementExamens;
use App\Entity\Utilisateur;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;

class DecisionObservationsTest extends ApiTestCaseCustom
{
    private const string DECISION = '/utilisateurs/beneficiaire-decision/decisions/2025';
    private const string URI = self::DECISION . '/observations';

    protected function tearDown(): void
    {
        // la décision des fixtures sert aux autres classes de test : validée, sans saisie
        $decision = $this->decision();
        $decision->setEtat(DecisionAmenagementExamens::ETAT_VALIDE);
        $decision->setObservations(null)->setDateAvisMedecin(null);
        static::getContainer()->get('doctrine')->getManager()->flush();

        parent::tearDown();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function etatsModifiablesProvider(): array
    {
        return [
            'en attente de validation' => [DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS],
            'validée' => [DecisionAmenagementExamens::ETAT_VALIDE],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function etatsEnvoyesProvider(): array
    {
        return [
            'édition demandée' => [DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE],
            'éditée' => [DecisionAmenagementExamens::ETAT_EDITE],
        ];
    }

    #[DataProvider('etatsModifiablesProvider')]
    public function testGestionnaireCanSaveObservationsBeforeDecisionIsSent(string $etat): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->etatDecision($etat);

        $client->request('PATCH', self::URI, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'observations' => 'Salle au rez-de-chaussée',
                'dateAvisMedecin' => '2026-06-15',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['observations' => 'Salle au rez-de-chaussée']);
        $decision = $this->decision();
        $this->assertSame('Salle au rez-de-chaussée', $decision->getObservations());
        $this->assertSame('2026-06-15', $decision->getDateAvisMedecin()?->format('Y-m-d'));
        // l'enregistrement des observations ne fait pas avancer la décision
        $this->assertSame($etat, $decision->getEtat());
    }

    public function testObservationsCannotChangeEtat(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $this->etatDecision(DecisionAmenagementExamens::ETAT_VALIDE);

        $client->request('PATCH', self::URI, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'etat' => DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE,
                'observations' => 'Tiers temps',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(DecisionAmenagementExamens::ETAT_VALIDE, $this->decision()->getEtat());
    }

    #[DataProvider('etatsEnvoyesProvider')]
    public function testObservationsAreLockedOnceDecisionIsSent(string $etat): void
    {
        $client = $this->createClientWithCredentials('admin');
        $this->etatDecision($etat);

        $client->request('PATCH', self::URI, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['observations' => 'Trop tard'],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertNull($this->decision()->getObservations());
    }

    public function testBeneficiaireCannotSaveObservations(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire-decision');
        $this->etatDecision(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);

        $client->request('PATCH', self::URI, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['observations' => 'Saisie non autorisée'],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testObservationsLengthIsValidated(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->etatDecision(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);

        $client->request('PATCH', self::URI, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['observations' => str_repeat('o', 4001)],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    /** La décision 2025 visée par l'URI : le bénéficiaire en a aussi une pour l'année en cours. */
    private function decision(): DecisionAmenagementExamens
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $manager->clear();
        $beneficiaire = $manager->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire-decision']);

        return $manager->getRepository(DecisionAmenagementExamens::class)->findOneBy([
            'beneficiaire' => $beneficiaire,
            'debut' => new DateTime('2025-09-01'),
        ]);
    }

    /** Après la création du client : la requête passe par la connexion de son noyau. */
    private function etatDecision(string $etat): void
    {
        $decision = $this->decision();
        $decision->setEtat($etat);
        static::getContainer()->get('doctrine')->getManager()->flush();
    }
}
