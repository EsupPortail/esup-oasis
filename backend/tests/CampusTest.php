<?php

namespace App\Tests;

class CampusTest extends ApiTestCaseCustom
{
    public function testUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/campus/1');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAdminCanReadCampus(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $response = $client->request('GET', '/campus');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');

        $this->assertJsonContains([
            '@context' => '/contexts/Campus',
            '@id' => '/campus',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGestionnaireCanReadCampus(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/campus');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
    }

    public function testAdminCanWriteCampus(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/campus', [
            'json' => [
                'libelle' => 'nouveau',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Campus',
            '@type' => 'Campus',
            'libelle' => 'nouveau',
            'actif' => true,
        ]);
    }

    public function testGestionnaireCannotWriteCampus(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/campus', [
            'json' => [
                'libelle' => 'nouveau',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanPatchCampus(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/campus/1', [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'libelle' => 'nouveau patch',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Campus',
            '@type' => 'Campus',
            '@id' => '/campus/1',
            'libelle' => 'nouveau patch',
        ]);
    }

    public function testGestionnaireCannotPatchCampus(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('PATCH', '/campus/1', [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'libelle' => 'nouveau',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
