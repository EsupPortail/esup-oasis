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

use App\Entity\ApplicationCliente;
use Symfony\Component\HttpFoundation\Response;

class ApplicationClienteTest extends ApiTestCaseCustom
{
    private function ensureClientAppExists(string $id = 'test_app', string $key = 'test_api_key'): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $repo = $em->getRepository(ApplicationCliente::class);
        $app = $repo->findOneBy(['identifiant' => $id]);
        if (!$app) {
            $app = new ApplicationCliente();
            $app->setIdentifiant($id);
            $app->setApiKey($key);
            $app->setDescription("Test client app $id");
            $em->persist($app);
            $em->flush();
        }
    }

    public function testAuthenticateAsApplication(): void
    {
        $this->ensureClientAppExists('test_app', 'test_api_key');

        $client = static::createClient([
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
        ]);

        $client->request('POST', '/connect/apikey', [
            'json' => [
                'appId' => 'test_app',
                'apiKey' => 'test_api_key',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('ey', $content);
    }

    public function testAppCanListEventsForIntervenant(): void
    {
        $this->ensureClientAppExists('test_app', 'test_api_key');
        $client = $this->createClientWithAppCredentials('test_app');

        $client->request('GET', '/evenements?intervenant=/utilisateurs/intervenant');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
    }

    public function testAppCanListEventsForBeneficiaire(): void
    {
        $this->ensureClientAppExists('test_app', 'test_api_key');
        $client = $this->createClientWithAppCredentials('test_app');

        $client->request('GET', '/evenements?beneficiaires=/utilisateurs/beneficiaire');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
    }

    public function testAppCanGetIndividualUser(): void
    {
        $this->ensureClientAppExists('test_app', 'test_api_key');
        $client = $this->createClientWithAppCredentials('test_app');

        $client->request('GET', '/utilisateurs/intervenant');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
    }

    public function testAppCanListEventTypes(): void
    {
        $this->ensureClientAppExists('test_app', 'test_api_key');
        $client = $this->createClientWithAppCredentials('test_app');

        $client->request('GET', '/types_evenements');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testAppCanListCampuses(): void
    {
        $this->ensureClientAppExists('test_app', 'test_api_key');
        $client = $this->createClientWithAppCredentials('test_app');

        $client->request('GET', '/campus');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
    }

    public function testAppCannotCreateEvent(): void
    {
        $this->ensureClientAppExists('test_app', 'test_api_key');
        $client = $this->createClientWithAppCredentials('test_app');

        $client->request('POST', '/evenements', [
            'json' => [
                'libelle' => 'Test event',
                'debut' => '2024-01-01T10:00:00Z',
                'fin' => '2024-01-01T11:00:00Z',
                'type' => '/types_evenements/1',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAppCannotModifyEvent(): void
    {
        $this->ensureClientAppExists('test_app', 'test_api_key');
        $client = $this->createClientWithAppCredentials('test_app');

        $client->request('PATCH', '/evenements/1', [
            'json' => [
                'libelle' => 'Modified by app',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAppCannotModifyUser(): void
    {
        $this->ensureClientAppExists('test_app', 'test_api_key');
        $client = $this->createClientWithAppCredentials('test_app');

        $client->request('PATCH', '/utilisateurs/intervenant', [
            'json' => [
                'nom' => 'Modified by app',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
