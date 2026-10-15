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

    public function testAdminCanPatchComposanteCircuitSignature(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/composantes/1');
        $referents = $client->getResponse()->toArray()['referents'];

        $client->request('PATCH', '/composantes/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'circuitSignature' => 'circuit-composante-1',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/composantes/1',
            'circuitSignature' => 'circuit-composante-1',
            'referents' => $referents,
        ]);

        // les référents se modifient sans toucher au circuit
        $client->request('PATCH', '/composantes/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'referents' => ['/utilisateurs/admin'],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['circuitSignature' => 'circuit-composante-1']);

        // un circuit vide rend la composante à l'envoi par e-mail
        $client->request('PATCH', '/composantes/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'circuitSignature' => '   ',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertNull($client->getResponse()->toArray()['circuitSignature'] ?? null);
    }

    public function testCircuitSignatureLengthIsValidated(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/composantes/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'circuitSignature' => str_repeat('c', 256),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }
}
