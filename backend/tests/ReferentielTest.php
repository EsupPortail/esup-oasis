<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Tests;

use Symfony\Component\HttpFoundation\Response;

class ReferentielTest extends ApiTestCaseCustom
{
    public function testGetTypologiesHandicap(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/typologies');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/TypologieHandicap',
            '@type' => 'hydra:Collection',
            '@id' => '/typologies',
        ]);
    }

    public function testGetCategoriesAmenagements(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/categories_amenagements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/CategorieAmenagement',
            '@type' => 'hydra:Collection',
            '@id' => '/categories_amenagements',
        ]);
    }

    public function testAdminCanCreateCategorieAmenagement(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/categories_amenagements', [
            'json' => [
                'libelle' => 'Nouvelle catégorie',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'libelle' => 'Nouvelle catégorie',
        ]);
    }

    public function testGetClubsSportifs(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/clubs_sportifs');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/ClubSportif',
            '@id' => '/clubs_sportifs',
        ]);
    }

    public function testAdminCanCreateClubSportif(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/clubs_sportifs', [
            'json' => [
                'libelle' => 'Nouveau club',
                'actif' => true,
                'centreFormation' => true,
                'professionnel' => false,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'libelle' => 'Nouveau club',
            'centreFormation' => true,
        ]);
    }

    public function testGetTypesAmenagements(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/types_amenagements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/TypeAmenagement',
            '@id' => '/types_amenagements',
        ]);
    }

    public function testAdminCanCreateTypeAmenagement(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/types_amenagements', [
            'json' => [
                'libelle' => 'Nouveau type',
                'categorie' => '/categories_amenagements/1',
                'actif' => true,
                'examens' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'libelle' => 'Nouveau type',
        ]);
    }

    public function testAdminCanPatchTypeAmenagement(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // Verify initial state
        $client->request('GET', '/types_amenagements');
        $this->assertResponseIsSuccessful();
        $initialData = json_decode($client->getResponse()->getContent(), true);
        $initialCount = $initialData['hydra:totalItems'];

        // Modify the label of the existing type amenagement with ID 1
        $client->request('PATCH', '/types_amenagements/1', [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'libelle' => 'Temps majoré modifié',
                'categorie' => '/categories_amenagements/1',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/types_amenagements/1',
            'libelle' => 'Temps majoré modifié',
        ]);

        // Verify that the total count hasn't changed (no new resource created)
        $client->request('GET', '/types_amenagements');
        $this->assertResponseIsSuccessful();
        $finalData = json_decode($client->getResponse()->getContent(), true);
        $finalCount = $finalData['hydra:totalItems'];

        $this->assertEquals($initialCount, $finalCount);
    }

    public function testGetCategoriesTags(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/categories_tags');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/CategorieTag',
            '@id' => '/categories_tags',
        ]);
    }

    public function testGetTags(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/tags');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Tag',
            '@id' => '/tags',
        ]);
    }

    public function testGetTypesDemandes(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/types_demandes');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/TypeDemande',
            '@id' => '/types_demandes',
        ]);
    }

    public function testGetEtatsDemandes(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/etats_demandes');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/EtatDemande',
            '@id' => '/etats_demandes',
        ]);
    }

    public function testGetCompetences(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/competences');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Competence',
            '@id' => '/competences',
        ]);
    }

    public function testAdminCanCreateCompetence(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/competences', [
            'json' => [
                'libelle' => 'Nouvelle compétence',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'libelle' => 'Nouvelle compétence',
        ]);
    }

    public function testGetDisciplinesArtistiques(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/disciplines_artistiques');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/DisciplineArtistique',
            '@id' => '/disciplines_artistiques',
        ]);
    }

    public function testAdminCanCreateDisciplineArtistique(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/disciplines_artistiques', [
            'json' => [
                'libelle' => 'Nouvel art',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'libelle' => 'Nouvel art',
        ]);
    }

    public function testGetEtablissementsEnseignementArtistique(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/etablissements_enseignement_artistique');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/EtablissementEnseignementArtistique',
            '@id' => '/etablissements_enseignement_artistique',
        ]);
    }

    public function testAdminCanCreateEtablissementEnseignementArtistique(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/etablissements_enseignement_artistique', [
            'json' => [
                'libelle' => 'Nouvel établissement',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'libelle' => 'Nouvel établissement',
        ]);
    }

    public function testGetTypesEngagements(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/types_engagements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/TypeEngagement',
            '@id' => '/types_engagements',
        ]);
    }

    public function testAdminCanCreateTypeEngagement(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/types_engagements', [
            'json' => [
                'libelle' => 'Engagement test',
                'actif' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'libelle' => 'Engagement test',
        ]);
    }

    /*
     * public function testGetTypesSuiviAmenagements(): void
     * {
     * $client = $this->createClientWithCredentials('admin');
     * $client->request('GET', '/types_suivi_amenagements');
     *
     * $this->assertResponseIsSuccessful();
     * $this->assertJsonContains([
     * '@context' => '/contexts/TypeSuiviAmenagement',
     * '@id' => '/types_suivi_amenagements',
     * ]);
     * }
     *
     * public function testAdminCanCreateTypeSuiviAmenagement(): void
     * {
     * $client = $this->createClientWithCredentials('admin');
     * $client->request('POST', '/types_suivi_amenagements', [
     * 'json' => [
     * 'libelle' => 'Suivi test',
     * 'actif' => true,
     * ],
     * ]);
     *
     * $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
     * $this->assertJsonContains([
     * 'libelle' => 'Suivi test',
     * ]);
     * }
     */
}
