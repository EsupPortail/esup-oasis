<?php

/*
 * Copyright (c) 2024. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Tests;

use Symfony\Component\HttpFoundation\Response;

class TauxHorairesTest extends ApiTestCaseCustom
{
    public function testPlanificateurRightsRequired(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/types_evenements/1/taux/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testFetchOneTaux(): void
    {
        $client = $this->createClientWithCredentials('intervenant');
        $client->request('GET', '/types_evenements/1/taux/1');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/TauxHoraire',
            '@type' => 'TauxHoraire',
            '@id' => '/types_evenements/1/taux/1',
        ]);
    }

    public function testCannotAccessTauxThroughWrongTypeEvenement(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/types_evenements/2/taux/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAdminCanCreateNewTaux(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/types_evenements/2/taux', [
            'json' => [
                'montant' => '12.12',
                'debut' => '2020-01-01',
                'fin' => '2020-12-31',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            '@context' => '/contexts/TauxHoraire',
            '@type' => 'TauxHoraire',
            '@id' => '/types_evenements/2/taux/3',
        ]);
    }

    public function testAdminCanModifyTaux(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/types_evenements/1/taux/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'montant' => '43.21',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/types_evenements/1/taux/1',
            'montant' => '43.21',
        ]);
    }

    public function testCannotCreateNewTauxIfActiveOneExists(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/types_evenements/1/taux', [
            'json' => [
                'montant' => '12.12',
                'debut' => '2020-01-01',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testFilterByDate(): void
    {
        $client = $this->createClientWithCredentials('admin');
        
        // Taux 1 for TypeEvenement 1 exists in fixtures, let's find its dates
        $client->request('GET', '/types_evenements/1/taux/1');
        $data = $client->getResponse()->toArray();
        $debut = $data['debut'];
        
        $client->request('GET', '/types_evenements/1/taux?date=' . urlencode($debut));
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => 1,
        ]);

        $client->request('GET', '/types_evenements/1/taux?date=1900-01-01');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => 0,
        ]);
    }

    public function testAdminCanDeleteTaux(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('DELETE', '/types_evenements/1/taux/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testMontantFieldValidation(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/types_evenements/2/taux', [
            'json' => [
                'montant' => '12,12', // Invalid decimal separator
                'debut' => '2020-01-01',
                'fin' => '2020-12-31',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
