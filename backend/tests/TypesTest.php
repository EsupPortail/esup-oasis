<?php

namespace App\Tests;

class TypesTest extends ApiTestCaseCustom
{
    public function testGetTypesEquipements(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/types_equipements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/TypeEquipement',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testAdminCanPatchTypeEquipement(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/types_equipements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'nouveau',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
    }

    public function testGetOneTypeEvenement(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/types_evenements/1');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/TypeEvenement',
            '@type' => 'TypeEvenement',
            '@id' => '/types_evenements/1',
        ]);
    }

    public function testAdminCanPatchTypeEvenement(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/types_evenements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'nouveau',
                'forfait' => true,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/types_evenements/1',
        ]);
    }

    public function testAdminCanCreateTypeEvenement(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/types_evenements', [
            'json' => [
                'libelle' => 'qfsgh',
                'actif' => true,
                'visibleParDefaut' => true,
                'couleur' => 'brown',
                'forfait' => false,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/TypeEvenement',
            '@type' => 'TypeEvenement',
        ]);
    }
}
