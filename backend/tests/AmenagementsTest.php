<?php

namespace App\Tests;

class AmenagementsTest extends ApiTestCaseCustom
{
    public function testGetTypesAmenagements(): void
    {
        $client = $this->createClientWithCredentials('demandeur');
        $response = $client->request('GET', '/types_amenagements');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');

        $this->assertJsonContains([
            '@context' => '/contexts/TypeAmenagement',
            '@type' => 'hydra:Collection',
            '@id' => '/types_amenagements',
        ]);
    }

    public function testAdminCanAddTypeAmenagement(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/types_amenagements', [
            'json' => [
                'libelle' => 'nouveau type aménagement',
                'pedagogique' => false,
                'examens' => true,
                'aideHumaine' => false,
                'categorie' => '/categories_amenagements/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');

        $this->assertJsonContains([
            '@context' => '/contexts/TypeAmenagement',
            'libelle' => 'nouveau type aménagement',
            'pedagogique' => false,
            'examens' => true,
        ]);
    }

    public function testGestionnaireCannotAddTypeAmenagement(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/types_amenagements', [
            'json' => [
                'libelle' => 'nouveau type aménagement',
                'pedagogique' => false,
                'examens' => true,
                'aideHumaine' => false,
                'categorie' => '/categories_amenagements/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGestionnaireCanAddAmenagementToBeneficiaire(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/beneficiaire/amenagements', [
            'json' => [
                'typeAmenagement' => '/types_amenagements/1',
                'semestre1' => true,
                'semestre2' => true,
                'debut' => '2025-01-01',
                'fin' => '2025-12-31',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'semestre1' => true,
            'typeAmenagement' => '/types_amenagements/1',
        ]);
    }

    public function testBeneficiaireCanSeeOwnAmenagements(): void
    {
        //combien on en avait?
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/utilisateurs/beneficiaire/amenagements');
        $before = $client->getResponse()->toArray()['hydra:totalItems'];

        // On en crée un d'abord
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/beneficiaire/amenagements', [
            'json' => [
                'typeAmenagement' => '/types_amenagements/1',
                'semestre1' => true,
                'semestre2' => true,
                'debut' => '2025-01-01',
                'fin' => '2025-12-31',
            ],
        ]);

        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/utilisateurs/beneficiaire/amenagements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => $before + 1,
        ]);
    }

    public function testBeneficiaireCannotSeeOtherAmenagements(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/utilisateurs/beneficiaire2/amenagements');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testSearchAmenagements(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');

        // Recherche (insensible à la casse)
        $client->request('GET', '/amenagements/utilisateurs?nom=beneficiaire');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => 1,
        ]);

        $client->request('GET', '/amenagements/utilisateurs?nom=notfound');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => 0,
        ]);
    }

    public function testGestionnaireCanDeleteAmenagement(): void
    {
        //on récupère un type d'aménagement
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/types_amenagements');
        $type = $client->getResponse()->toArray()['hydra:member'][0]['@id'];

        $client = $this->createClientWithCredentials('gestionnaire');
        $response = $client->request('POST', '/utilisateurs/beneficiaire/amenagements', [
            'json' => [
                'typeAmenagement' => $type,
                'semestre1' => true,
                'semestre2' => true,
                'debut' => '2025-01-01',
                'fin' => '2025-12-31',
            ],
        ]);
        $data = $response->toArray();
        $id = $data['@id'];

        $client->request('DELETE', $id);

        $this->assertResponseStatusCodeSame(204);
    }
}
