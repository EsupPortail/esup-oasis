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

class EvenementsVisibilityTest extends ApiTestCaseCustom
{
    /**
     * @return int[]
     */
    private function getEventIds(?string $uid = null): array
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository(\App\Entity\Evenement::class);

        if ($uid === null) {
            return array_map(fn($e) => $e->getId(), $repository->findAll());
        }

        $qb = $repository->createQueryBuilder('e');
        $qb->select('distinct e.id')
            ->leftJoin('e.intervenant', 'i')
            ->leftJoin('i.utilisateur', 'iu')
            ->leftJoin('e.beneficiaires', 'b')
            ->leftJoin('b.utilisateur', 'bu')
            ->where('iu.uid = :uid OR bu.uid = :uid')
            ->setParameter('uid', $uid);

        return array_column($qb->getQuery()->getScalarResult(), 'id');
    }

    public function testAdminCanSeeAllEvenements(): void
    {
        $ids = $this->getEventIds();
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => count($ids)]);
        
        if (!empty($ids)) {
            $client->request('GET', '/evenements/' . $ids[0]);
            $this->assertResponseIsSuccessful();
        }
    }

    public function testGestionnaireCanSeeAllEvenements(): void
    {
        $ids = $this->getEventIds();
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => count($ids)]);
        
        if (!empty($ids)) {
            $client->request('GET', '/evenements/' . $ids[0]);
            $this->assertResponseIsSuccessful();
        }
    }

    public function testRenfortCanSeeAllEvenements(): void
    {
        $ids = $this->getEventIds();
        $client = $this->createClientWithCredentials('renfort');
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => count($ids)]);
        
        if (!empty($ids)) {
            $client->request('GET', '/evenements/' . $ids[0]);
            $this->assertResponseIsSuccessful();
        }
    }

    public function testAppCanSeeAllEvenements(): void
    {
        $ids = $this->getEventIds();
        $client = $this->createClientWithAppCredentials('test_app');
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => count($ids)]);
        
        if (!empty($ids)) {
            $client->request('GET', '/evenements/' . $ids[0]);
            $this->assertResponseIsSuccessful();
        }
    }

    public function testIntervenant1CanSeeOnlyOwnEvenements(): void
    {
        $uid = 'intervenant';
        $ownIds = $this->getEventIds($uid);
        $allIds = $this->getEventIds();
        $otherIds = array_values(array_diff($allIds, $ownIds));

        $client = $this->createClientWithCredentials($uid);
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => count($ownIds)]);
        
        if (!empty($ownIds)) {
            $client->request('GET', '/evenements/' . $ownIds[0]);
            $this->assertResponseIsSuccessful();
        }

        if (!empty($otherIds)) {
            $client->request('GET', '/evenements/' . $otherIds[0]);
            $this->assertResponseStatusCodeSame(403);
        }
    }

    public function testIntervenant2CanSeeOnlyOwnEvenements(): void
    {
        $uid = 'intervenant2';
        $ownIds = $this->getEventIds($uid);
        $allIds = $this->getEventIds();
        $otherIds = array_values(array_diff($allIds, $ownIds));

        $client = $this->createClientWithCredentials($uid);
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => count($ownIds)]);
        
        if (!empty($ownIds)) {
            $client->request('GET', '/evenements/' . $ownIds[0]);
            $this->assertResponseIsSuccessful();
        }

        if (!empty($otherIds)) {
            $client->request('GET', '/evenements/' . $otherIds[0]);
            $this->assertResponseStatusCodeSame(403);
        }
    }

    public function testBeneficiaireCanSeeOwnEvenements(): void
    {
        $uid = 'beneficiaire';
        $ownIds = $this->getEventIds($uid);
        $client = $this->createClientWithCredentials($uid);
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => count($ownIds)]);
        
        if (!empty($ownIds)) {
            $client->request('GET', '/evenements/' . $ownIds[0]);
            $this->assertResponseIsSuccessful();
        }
    }

    public function testBeneficiaire2CanSeeNothing(): void
    {
        $uid = 'beneficiaire2';
        $ownIds = $this->getEventIds($uid);
        $allIds = $this->getEventIds();

        $client = $this->createClientWithCredentials($uid);
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => count($ownIds)]);
        
        if (!empty($allIds)) {
            // Should not be able to see an event he is not part of
            $client->request('GET', '/evenements/' . $allIds[0]);
            $this->assertResponseStatusCodeSame(403);
        }
    }

    public function testDemandeurCanSeeNothing(): void
    {
        $uid = 'demandeur';
        $ownIds = $this->getEventIds($uid);
        $allIds = $this->getEventIds();

        $client = $this->createClientWithCredentials($uid);
        $client->request('GET', '/evenements');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => count($ownIds)]);

        if (!empty($allIds)) {
            $client->request('GET', '/evenements/' . $allIds[0]);
            $this->assertResponseStatusCodeSame(403);
        }
    }
}
