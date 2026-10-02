<?php

namespace App\Tests;

class CommissionsTest extends ApiTestCaseCustom
{
    public function testGetCommissions(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/commissions');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Commission',
            '@type' => 'hydra:Collection',
            '@id' => '/commissions',
        ]);
    }

    public function testAdminCanPatchCommission(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/commissions/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'lalalalala',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/commissions/1',
            'libelle' => 'lalalalala',
        ]);
    }

    public function testGestionnaireCannotPatchCommission(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('PATCH', '/commissions/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'lalalalala',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanCreateCommission(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/commissions', [
            'json' => [
                'libelle' => 'lalalalala',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'libelle' => 'lalalalala',
        ]);
    }

    public function testGestionnaireCannotCreateCommission(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/commissions', [
            'json' => [
                'libelle' => 'lalalalala',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
