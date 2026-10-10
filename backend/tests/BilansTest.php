<?php

namespace App\Tests;

use App\ApiResource\ActiviteBeneficiaire;
use App\ApiResource\ActiviteIntervenant;

class BilansTest extends ApiTestCaseCustom
{
    public function testGetExtractionIntervenants(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $response = $client->request('GET', '/suivis/intervenants');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');

        $this->assertJsonContains([
            '@context' => '/contexts/ActiviteIntervenant',
            '@type' => 'hydra:Collection',
            '@id' => '/suivis/intervenants',
        ]);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        if (count($data['hydra:member']) > 0) {
            $member = $data['hydra:member'][0];
            $this->assertArrayHasKey('utilisateur', $member);
            $this->assertArrayHasKey('campus', $member);
            $this->assertArrayHasKey('type', $member);
            $this->assertArrayHasKey('tauxHoraire', $member);
            $this->assertArrayHasKey('nbHeures', $member);
            $this->assertArrayHasKey('nbEvenements', $member);
        }
    }

    public function testGetExtractionBeneficiaires(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $response = $client->request('GET', '/suivis/beneficiaires');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');

        $this->assertJsonContains([
            '@context' => '/contexts/ActiviteBeneficiaire',
            '@type' => 'hydra:Collection',
            '@id' => '/suivis/beneficiaires',
        ]);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        if (count($data['hydra:member']) > 0) {
            $member = $data['hydra:member'][0];
            $this->assertArrayHasKey('utilisateur', $member);
            $this->assertArrayHasKey('campus', $member);
            $this->assertArrayHasKey('type', $member);
            $this->assertArrayHasKey('tauxHoraire', $member);
            $this->assertArrayHasKey('nbHeures', $member);
            $this->assertArrayHasKey('nbEvenements', $member);
        }
    }

    public function testFilterResultsByPeriodeRH(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/suivis/intervenants?periode[]=/periodes/1&periode[]=/periodes/2');

        $this->assertResponseIsSuccessful();
    }

    public function testFilterIntervenantsByCampus(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/suivis/intervenants?campus=/campus/1');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertNotEmpty($data['hydra:member']);
        foreach ($data['hydra:member'] as $item) {
            $campusId = is_string($item['campus'] ?? null) ? $item['campus'] : $item['campus']['@id'] ?? null;
            if (null !== $campusId) {
                $this->assertEquals('/campus/1', $campusId);
            }
        }
    }

    public function testFilterIntervenantsByType(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/suivis/intervenants?type=/types_evenements/1');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertNotEmpty($data['hydra:member']);
        foreach ($data['hydra:member'] as $item) {
            $typeId = is_string($item['type']) ? $item['type'] : $item['type']['@id'] ?? null;
            $this->assertEquals('/types_evenements/1', $typeId);
        }
    }

    public function testFilterBeneficiairesByProfil(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/suivis/beneficiaires?beneficiaires.profil=/profils/1');

        $this->assertResponseIsSuccessful();
    }

    public function testIntervenantCannotAccessSuivis(): void
    {
        $client = $this->createClientWithCredentials('intervenant');
        $client->request('GET', '/suivis/intervenants');
        $this->assertResponseStatusCodeSame(403);

        $client->request('GET', '/suivis/beneficiaires');
        $this->assertResponseStatusCodeSame(403);
    }
}
