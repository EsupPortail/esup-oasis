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

use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\Response;

class CommissionMembresTest extends ApiTestCaseCustom
{
    public function testGetMembresCollection(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/commissions/1/membres');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/MembreCommission',
            '@id' => '/commissions/1/membres',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGetOneMembre(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/commissions/1/membres/membrecommission');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'MembreCommission',
            'utilisateur' => '/utilisateurs/membrecommission',
        ]);
    }

    public function testAdminCanAddMembreToCommission(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // On ajoute 'intervenant' à la commission 1
        $client->request('PUT', '/commissions/1/membres/intervenant', [
            'json' => [
                'roles' => [Utilisateur::ROLE_ATTRIBUER_PROFIL],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'utilisateur' => '/utilisateurs/intervenant',
            'roles' => [Utilisateur::ROLE_ATTRIBUER_PROFIL],
        ]);
    }

    public function testAdminCanUpdateMembreRoles(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // On récupère l'IRI réelle
        $client->request('GET', '/commissions/1/membres');
        $data = $client->getResponse()->toArray();
        $iri = $data['hydra:member'][0]['@id'];

        $client->request('PUT', $iri, [
            'json' => [
                'roles' => [Utilisateur::ROLE_VALIDER_CONFORMITE_DEMANDE],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'roles' => [Utilisateur::ROLE_VALIDER_CONFORMITE_DEMANDE],
        ]);
    }

    public function testAdminCanDeleteMembre(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // On récupère l'ID réel via la collection
        $client->request('GET', '/commissions/1/membres');
        $data = $client->getResponse()->toArray();
        $iri = $data['hydra:member'][0]['@id'];

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testNonAdminCannotManageMembres(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('PUT', '/commissions/1/membres/intervenant', [
            'json' => [
                'roles' => [Utilisateur::ROLE_ATTRIBUER_PROFIL],
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanCreateAndModifyMembreWithoutDuplicates(): void
    {
        $client = $this->createClientWithCredentials('admin');

        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $repo = $em->getRepository(\App\Entity\MembreCommission::class);

        $initialCount = $repo->count([]);

        // 1. On crée le membre
        $client->request('PUT', '/commissions/1/membres/intervenant2', [
            'json' => [
                'roles' => [Utilisateur::ROLE_ATTRIBUER_PROFIL],
            ],
        ]);
        $this->assertResponseIsSuccessful();

        // Vérifie que le count a augmenté de 1
        $this->assertSame($initialCount + 1, $repo->count([]));

        // 2. On modifie le membre
        $client->request('PUT', '/commissions/1/membres/intervenant2', [
            'json' => [
                'roles' => [Utilisateur::ROLE_VALIDER_CONFORMITE_DEMANDE],
            ],
        ]);
        $this->assertResponseIsSuccessful();

        // Vérifie que le count n'a pas augmenté (pas de doublon)
        $this->assertSame($initialCount + 1, $repo->count([]));

        // Vérifie qu'il y a bien une seule entrée pour cet utilisateur et cette commission
        $membres = $repo->findBy([
            'utilisateur' => $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'intervenant2']),
            'commission' => $em->getRepository(\App\Entity\Commission::class)->find(1),
        ]);
        $this->assertCount(1, $membres);
        $this->assertSame([Utilisateur::ROLE_VALIDER_CONFORMITE_DEMANDE], $membres[0]->getRoles());
    }
}
