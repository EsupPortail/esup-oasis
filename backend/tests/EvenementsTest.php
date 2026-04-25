<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Tests;

class EvenementsTest extends ApiTestCaseCustom
{
    // --- LECTURE (GET) ---

    public function testUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/evenements/1');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAdminCanListEvenements(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Evenement',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
        $this->assertJsonContains(['hydra:member' => [['@id' => '/evenements/1']]]);
    }

    public function testPagination(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/evenements?itemsPerPage=1&page=2');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => 2,
        ]);
        $data = $client->getResponse()->toArray();
        $this->assertCount(1, $data['hydra:member']);
        $this->assertStringContainsString('/evenements/2', $data['hydra:member'][0]['@id']);
    }

    public function testSearchFilters(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/evenements?utilisateurCreation=/utilisateurs/admin&debut[after]=2020-01-01');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Evenement',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testBeneficiaireCanSeeOwnEvents(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => 2,
        ]);
    }

    public function testIntervenantCanSeeOwnEvents(): void
    {
        $client = $this->createClientWithCredentials('intervenant');
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => 1,
        ]);
    }

    // --- MODIFICATIONS (POST, PATCH, DELETE) ---

    public function testGestionnaireCanPostEvent(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/evenements', [
            'json' => [
                'debut' => '2030-06-01T08:00',
                'fin' => '2030-06-01T12:00',
                'libelle' => 'nouveau',
                'type' => '/types_evenements/1',
                'campus' => '/campus/1',
                'beneficiaires' => ['/utilisateurs/beneficiaire'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'campus' => '/campus/1',
            'type' => '/types_evenements/1',
            'beneficiaires' => ['/utilisateurs/beneficiaire'],
        ]);
    }

    public function testBeneficiaireCannotHaveOverlappingEvents(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/evenements', [
            'json' => [
                'debut' => '2030-01-01T07:00',
                'fin' => '2030-01-01T09:00',
                'libelle' => 'chevauchement',
                'type' => '/types_evenements/1',
                'campus' => '/campus/1',
                'beneficiaires' => ['/utilisateurs/beneficiaire'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testIntervenantCannotHaveOverlappingEvents(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/evenements', [
            'json' => [
                'debut' => '2030-01-01T07:00',
                'fin' => '2030-01-01T09:00',
                'libelle' => 'chevauchement',
                'type' => '/types_evenements/1',
                'campus' => '/campus/1',
                'beneficiaires' => ['/utilisateurs/beneficiaire2'],
                'intervenant' => '/utilisateurs/intervenant',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAdminCanPatchEventBeneficiaires(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/evenements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'beneficiaires' => ['/utilisateurs/beneficiaire', '/utilisateurs/beneficiaire2'],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/evenements/1',
        ]);
    }

    public function testCannotUseUserWithoutProfilAsBeneficiaire(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/evenements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'beneficiaires' => ['/utilisateurs/admin'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAdminCanPatchEquipements(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/evenements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'equipements' => ['/types_equipements/1'],
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testEventCannotEndBeforeStart(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/evenements', [
            'json' => [
                'debut' => '2021-01-01T08:00',
                'fin' => '2020-01-01T12:00',
                'libelle' => '',
                'type' => '/types_evenements/1',
                'campus' => '/campus/1',
                'beneficiaires' => ['/utilisateurs/beneficiaire'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCannotModifyEventSentToHR(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/evenements/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'modif sans impact',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAdminCanPatchTeachers(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/evenements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'enseignants' => [
                    '/utilisateurs/enseignant',
                    '/utilisateurs/enseignant2',
                ],
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testAdminCanPatchTempsPreparationAndSupplementaire(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/evenements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'tempsPreparation' => 15,
                'tempsSupplementaire' => 30,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/evenements/1',
            'tempsPreparation' => 15,
            'tempsSupplementaire' => 30,
        ]);
    }

    public function testAdminCanResetTempsPreparationAndSupplementaireToZero(): void
    {
        $client = $this->createClientWithCredentials('admin');
        // On s'assure d'abord qu'ils ont une valeur non nulle
        $client->request('PATCH', '/evenements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'tempsPreparation' => 15,
                'tempsSupplementaire' => 30,
            ],
        ]);
        $this->assertResponseIsSuccessful();

        // Puis on repasse à 0
        $client->request('PATCH', '/evenements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'tempsPreparation' => 0,
                'tempsSupplementaire' => 0,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/evenements/1',
            'tempsPreparation' => 0,
            'tempsSupplementaire' => 0,
        ]);
    }

    public function testAdminCanResetIntervenantToNull(): void
    {
        $client = $this->createClientWithCredentials('admin');
        // On s'assure d'abord qu'il y a un intervenant (ce qui est le cas en fixtures pour /evenements/1)
        $client->request('GET', '/evenements/1');
        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertNotNull($data['intervenant']);

        // Puis on le repasse à null
        $client->request('PATCH', '/evenements/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'intervenant' => null,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = $client->getResponse()->toArray();

        $this->assertArrayNotHasKey('intervenant', $responseData);
        $this->assertJsonContains([
            '@id' => '/evenements/1',
        ]);
    }

    public function testPlanificateurCanDeleteEvent(): void
    {
        $client = $this->createClientWithCredentials('renfort');
        $client->request('DELETE', '/evenements/1');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testCannotDeleteEventSentToHR(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('DELETE', '/evenements/2');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGestionnaireCanSeeEventsToValidate(): void
    {
        $client = $this->createClientWithCredentials('renfort');
        $client->request('POST', '/evenements', [
            'json' => [
                'debut' => '2025-01-01T08:00',
                'fin' => '2025-01-01T12:00',
                'libelle' => 'inter renfort',
                'type' => '/types_evenements/-1',
                'campus' => '/campus/1',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client = $this->createClientWithCredentials('gestionnaire2');
        $client->request('GET', '/evenements?aValider=true');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => 1,
        ]);
    }

    public function testGestionnaireOnlySeesOwnServiceEventsToValidate(): void
    {
        $client = $this->createClientWithCredentials('renfort');
        $client->request('POST', '/evenements', [
            'json' => [
                'debut' => '2025-01-01T08:00',
                'fin' => '2025-01-01T12:00',
                'libelle' => 'inter renfort',
                'type' => '/types_evenements/-1',
                'campus' => '/campus/1',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/evenements?aValider=true');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => 0,
        ]);
    }

    public function testGestionnaireCanConfirmRenfortIntervention(): void
    {
        $client = $this->createClientWithCredentials('renfort');
        $client->request('POST', '/evenements', [
            'json' => [
                'debut' => '2025-01-01T08:00',
                'fin' => '2025-01-01T12:00',
                'libelle' => 'inter renfort',
                'type' => '/types_evenements/-1',
                'campus' => '/campus/1',
            ],
        ]);
        $data = $client->getResponse()->toArray();
        $id = $data['@id'];

        $client = $this->createClientWithCredentials('gestionnaire2');
        $client->request('PATCH', $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'valide' => true,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertNotNull($data['dateValidation']);
    }

    public function testRenfortCanCreateOwnEvents(): void
    {
        $client = $this->createClientWithCredentials('renfort');
        $client->request('POST', '/evenements', [
            'json' => [
                'debut' => '2040-01-01T08:00',
                'fin' => '2040-01-01T12:00',
                'libelle' => 'mon activité',
                'type' => '/types_evenements/-1',
                'campus' => '/campus/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }
}
