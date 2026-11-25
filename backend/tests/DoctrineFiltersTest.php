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

class DoctrineFiltersTest extends ApiTestCaseCustom
{
    /**
     * Vérifie que les filtres Doctrine personnalisés résistent aux tentatives d'injection DQL
     */
    public function testDoctrineFiltersDqlInjectionResistance(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // 1. CaseInsensitiveOrderFilter : propriété non autorisée / injection dans la clé ou la direction
        $client->request('GET', '/campus?order[libelle))=asc,%20o_1.id--]=asc');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/campus?order[libelle]=ASC,%201=1');
        $this->assertResponseIsSuccessful();

        // 2. InterventionForfaitNomIntervenantOrderFilter : direction malicieuse
        $client->request('GET', '/interventions_forfait?order[intervenant.utilisateur.nom]=ASC,%201=1');
        $this->assertResponseIsSuccessful();

        // 3. PreloadAssociationsFilter : tentative d'injection via query string
        $client->request('GET', '/beneficiaires?preloadAssociations[hack][sourceEntity]=root&preloadAssociations[hack][relationName]=inscriptions%20WITH%201=1--');
        $this->assertResponseIsSuccessful();
    }
}
