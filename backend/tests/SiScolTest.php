<?php

namespace App\Tests;

class SiScolTest extends ApiTestCaseCustom
{
    public function testGetComposantes(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/composantes');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Composante',
            '@type' => 'hydra:Collection',
            '@id' => '/composantes',
        ]);
    }

    public function testGetFormations(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/formations');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Formation',
            '@type' => 'hydra:Collection',
            '@id' => '/formations',
        ]);
    }

    public function testAdminCanPatchComposanteReferents(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/composantes/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'referents' => ['/utilisateurs/enseignant'],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/composantes/1',
        ]);
        $data = $client->getResponse()->toArray();
        $referents = array_map(fn($r) => is_string($r) ? $r : $r['@id'], $data['referents']);
        $this->assertContains('/utilisateurs/enseignant', $referents);

        $client->request('GET', '/utilisateurs/enseignant');
        $this->assertResponseIsSuccessful();
        $userData = $client->getResponse()->toArray();
        $this->assertContains('ROLE_REFERENT_COMPOSANTE', $userData['roles']);

        // Retrait du référent
        $client->request('PATCH', '/composantes/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'referents' => [],
            ],
        ]);
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/utilisateurs/enseignant');
        $this->assertResponseIsSuccessful();
        $userData = $client->getResponse()->toArray();
        $this->assertNotContains('ROLE_REFERENT_COMPOSANTE', $userData['roles']);
    }
}
