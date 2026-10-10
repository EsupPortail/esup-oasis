<?php

namespace App\Tests;

class ServicesTest extends ApiTestCaseCustom
{
    public function testAuthRequiredForServiceItem(): void
    {
        $client = static::createClient();
        $client->request('GET', '/services/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAdminCanListServices(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/services');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Service',
            '@id' => '/services',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGestionnaireCanListServices(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/services');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Service',
            '@id' => '/services',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testAdminCanCreateService(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/services', [
            'json' => [
                'libelle' => 'nouveau',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Service',
            '@type' => 'Service',
        ]);
    }

    public function testAdminCanPatchService(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/services/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'nouveau',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Service',
            '@type' => 'Service',
            '@id' => '/services/1',
            'libelle' => 'nouveau',
        ]);
    }

    public function testGestionnaireCannotPatchService(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('PATCH', '/services/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'nouveau',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
