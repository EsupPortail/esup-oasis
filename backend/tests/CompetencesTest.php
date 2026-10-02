<?php

namespace App\Tests;

class CompetencesTest extends ApiTestCaseCustom
{
    public function testGetCompetences(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/competences');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Competence',
            '@id' => '/competences',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testAdminCanCreateCompetence(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/competences', [
            'json' => [
                'libelle' => 'nouveau',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/Competence',
            '@type' => 'Competence',
            'libelle' => 'nouveau',
        ]);
    }

    public function testGestionnaireCannotCreateCompetence(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/competences', [
            'json' => [
                'libelle' => 'nouveau',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
