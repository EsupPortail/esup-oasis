<?php

namespace App\Tests;

use App\Entity\AvisEse;
use App\Entity\Utilisateur;
use DateTime;

class AvisEseTest extends ApiTestCaseCustom
{
    public function testAdminCanListAvisEse(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/utilisateurs/beneficiaire/avis_ese');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');

        $this->assertJsonContains([
            '@context' => '/contexts/AvisEse',
            '@type' => 'hydra:Collection',
            '@id' => '/utilisateurs/beneficiaire/avis_ese',
        ]);
    }

    public function testAdminCanCreateAvisEse(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/utilisateurs/beneficiaire/avis_ese', [
            'json' => [
                'libelle' => 'Nouvel avis ESE',
                'commentaire' => 'Commentaire de test',
                'debut' => new DateTime()->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'libelle' => 'Nouvel avis ESE',
            'commentaire' => 'Commentaire de test',
        ]);
    }

    public function testGestionnaireCanCreateAvisEseForTheirBeneficiaire(): void
    {
        // gestionnaire manages beneficiaire1 (@beneficiaire)
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/beneficiaire/avis_ese', [
            'json' => [
                'libelle' => 'Avis par gestionnaire',
                'debut' => new DateTime()->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testBeneficiaireCannotCreateOwnAvisEse(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('POST', '/utilisateurs/beneficiaire/avis_ese', [
            'json' => [
                'libelle' => 'Mon propre avis',
                'debut' => new DateTime()->format('Y-m-d'),
            ],
        ]);

        // Expect 403 if security is correctly implemented,
        // but currently it might be 201 or 500 (due to the bug I found)
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanUpdateAvisEse(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire2']);

        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/utilisateurs/beneficiaire2/avis_ese/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'Modifié',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'libelle' => 'Modifié',
        ]);
    }

    public function testAdminCanDeleteAvisEse(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire2']);

        $client = $this->createClientWithCredentials('admin');
        $client->request('DELETE', '/utilisateurs/beneficiaire2/avis_ese/1');

        $this->assertResponseStatusCodeSame(204);

        $em->clear();
        $deletedAvis = $em->getRepository(AvisEse::class)->find(1);
        $this->assertNull($deletedAvis);
    }
}
