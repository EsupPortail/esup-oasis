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

class EntretiensTest extends ApiTestCaseCustom
{
    public function testGestionnaireCanListEntretiens(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/utilisateurs/beneficiaire/entretiens');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Entretien',
            '@id' => '/utilisateurs/beneficiaire/entretiens',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGestionnaireCanCreateEntretien(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('POST', '/utilisateurs/beneficiaire/entretiens', [
            'json' => [
                'commentaire' => 'Entretien de test',
                'date' => '2026-03-11T10:00:00+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'commentaire' => 'Entretien de test',
            'gestionnaire' => '/utilisateurs/gestionnaire',
        ]);
    }

    public function testBeneficiaireCannotSeeOwnEntretiens(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/utilisateurs/beneficiaire/entretiens');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBeneficiaireCannotSeeOtherEntretiens(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/utilisateurs/beneficiaire2/entretiens');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBeneficiaireCannotCreateUpdateOrDeleteEntretien(): void
    {
        $tokenGestionnaire = $this->getTokenForUser('gestionnaire');
        $tokenBeneficiaire = $this->getTokenForUser('beneficiaire');
        $client = $this->createClientWithCredentials('beneficiaire');

        // Création d'un entretien par le gestionnaire pour tester GET/PATCH/DELETE unitaire
        $client->request('POST', '/utilisateurs/beneficiaire/entretiens', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenGestionnaire],
            'json' => [
                'commentaire' => 'Entretien confidentiel',
                'date' => '2026-03-11T10:00:00+00:00',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $entretienIri = $client->getResponse()->toArray()['@id'];

        $client->request('GET', $entretienIri, [
            'headers' => ['Authorization' => 'Bearer ' . $tokenBeneficiaire],
        ]);
        $this->assertResponseStatusCodeSame(403);

        $client->request('PATCH', $entretienIri, [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokenBeneficiaire,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => ['commentaire' => 'Piraté'],
        ]);
        $this->assertResponseStatusCodeSame(403);

        $client->request('DELETE', $entretienIri, [
            'headers' => ['Authorization' => 'Bearer ' . $tokenBeneficiaire],
        ]);
        $this->assertResponseStatusCodeSame(403);

        $client->request('POST', '/utilisateurs/beneficiaire/entretiens', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenBeneficiaire],
            'json' => [
                'commentaire' => 'Mon propre entretien',
                'date' => '2026-03-11T10:00:00+00:00',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
