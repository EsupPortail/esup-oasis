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

class BeneficiairesProfilsTest extends ApiTestCaseCustom
{
    public function testAuthenticationRequired(): void
    {
        $client = static::createClient();
        $client->request('GET', '/utilisateurs/beneficiaire/profils/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testFetchOneProfil(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/utilisateurs/beneficiaire/profils/1');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/BeneficiaireProfil',
            '@type' => 'BeneficiaireProfil',
            '@id' => '/utilisateurs/beneficiaire/profils/1',
        ]);
    }

    public function testNonAuthorizedUserCannotFetch(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire2');
        $client->request('GET', '/utilisateurs/beneficiaire/profils/1');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAddBeneficiaire(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/nouveau-beneficiaire/profils', [
            'json' => [
                'profil' => '/profils/3',
                'debut' => '2020-01-01',
                'gestionnaire' => '/utilisateurs/gestionnaire',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/BeneficiaireProfil',
            '@type' => 'BeneficiaireProfil',
            '@id' => '/utilisateurs/nouveau-beneficiaire/profils/4',
            'profil' => '/profils/3',
            'gestionnaire' => '/utilisateurs/gestionnaire',
        ]);
    }

    public function testAddBeneficiaireWithExplicitProfil(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/demandeur/profils', [
            'json' => [
                'profil' => '/profils/7',
                'debut' => '2020-01-01',
                'gestionnaire' => '/utilisateurs/gestionnaire',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'profil' => '/profils/7',
        ]);
    }

    public function testAddBeneficiaireWithRequiredTypologie(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/demandeur/profils', [
            'json' => [
                'profil' => '/profils/1',
                'typologies' => ['/typologies/1'],
                'debut' => '2020-01-01',
                'gestionnaire' => '/utilisateurs/gestionnaire',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'profil' => '/profils/1',
            'typologies' => [
                '/typologies/1',
            ],
        ]);
    }

    public function testAddBeneficiaireWithoutRequiredTypologieFails(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/nouveau-beneficiaire/profils', [
            'json' => [
                'profil' => '/profils/1',
                'debut' => '2020-01-01',
                'gestionnaire' => '/utilisateurs/gestionnaire',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testAddBeneficiaireWithoutProfil(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/nouveau-beneficiaire/profils', [
            'json' => [
                'debut' => '2020-01-01',
                'gestionnaire' => '/utilisateurs/gestionnaire',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            'profil' => '/profils/-1',
            'gestionnaire' => '/utilisateurs/gestionnaire',
        ]);
    }

    public function testModifyExistingBeneficiaire(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('PATCH', '/utilisateurs/beneficiaire/profils/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'fin' => '2060-01-01',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/utilisateurs/beneficiaire/profils/1',
            'fin' => '2060-01-01T00:00:00+00:00',
        ]);
    }

    public function testSpecifyTypologies(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('PATCH', '/utilisateurs/beneficiaire2/profils/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'typologies' => ['/typologies/1'],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/utilisateurs/beneficiaire2/profils/2',
        ]);

        $data = $client->getResponse()->toArray();
        $this->assertArrayHasKey('typologies', $data);
        $this->assertContains('/typologies/1', $data['typologies']);
    }

    public function testBeneficiaireCannotBeHisOwnGestionnaire(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('PATCH', '/utilisateurs/beneficiaire/profils/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'gestionnaire' => '/utilisateurs/beneficiaire',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testAddSecondProfilBeneficiaire(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/beneficiaire2/profils', [
            'json' => [
                'profil' => '/profils/3',
                'debut' => '2020-01-01',
                'gestionnaire' => '/utilisateurs/gestionnaire',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/BeneficiaireProfil',
            '@type' => 'BeneficiaireProfil',
            'profil' => '/profils/3',
            'gestionnaire' => '/utilisateurs/gestionnaire',
        ]);
    }

    public function testDeleteBeneficiaire(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('DELETE', '/utilisateurs/beneficiaire2/profils/2');

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testCannotDeleteBeneficiaireWithEvents(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('DELETE', '/utilisateurs/beneficiaire/profils/1');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
