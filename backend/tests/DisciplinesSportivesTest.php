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

class DisciplinesSportivesTest extends ApiTestCaseCustom
{
    public function testGetDisciplinesList(): void
    {
        $client = $this->createClientWithCredentials('demandeur');
        $client->request('GET', '/disciplines_sportives');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/DisciplineSportive',
            '@type' => 'hydra:Collection',
            '@id' => '/disciplines_sportives',
        ]);
    }

    public function testAdminCanAddDiscipline(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/disciplines_sportives', [
            'json' => [
                'libelle' => 'nouvelle discipline',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains([
            '@context' => '/contexts/DisciplineSportive',
            'libelle' => 'nouvelle discipline',
            'actif' => true,
        ]);
    }

    public function testGestionnaireCannotAddDiscipline(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/disciplines_sportives', [
            'json' => [
                'libelle' => 'nouvelle discipline',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
