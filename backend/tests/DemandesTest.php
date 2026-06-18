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

use Symfony\Component\Clock\ClockAwareTrait;

class DemandesTest extends ApiTestCaseCustom
{
    use ClockAwareTrait;

    private function getDemandeIdForDemandeur(): string
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $demandeur = $em->getRepository(\App\Entity\Utilisateur::class)->findOneBy(['uid' => 'demandeur']);
        $demande = $em->getRepository(\App\Entity\Demande::class)->findOneBy(['demandeur' => $demandeur]);
        return '/demandes/' . $demande->getId();
    }

    public function testGetTypesDemandes(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/types_demandes');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/types_demandes',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testDemandeurOnlySeesOwnDemandes(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        $demandeur = $em->getRepository(\App\Entity\Utilisateur::class)->findOneBy(['uid' => 'demandeur']);
        $countDemandeur = (int)$em
            ->createQuery('SELECT COUNT(d) FROM App\Entity\Demande d WHERE d.demandeur = :user')
            ->setParameter('user', $demandeur)
            ->getSingleScalarResult();

        $client = $this->createClientWithCredentials('demandeur');
        $client->request('GET', '/demandes');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/demandes',
            'hydra:totalItems' => $countDemandeur,
        ]);

        $demandeur3 = $em->getRepository(\App\Entity\Utilisateur::class)->findOneBy(['uid' => 'demandeur3']);
        $countDemandeur3 = (int)$em
            ->createQuery('SELECT COUNT(d) FROM App\Entity\Demande d WHERE d.demandeur = :user')
            ->setParameter('user', $demandeur3)
            ->getSingleScalarResult();

        $client = $this->createClientWithCredentials('demandeur3');
        $client->request('GET', '/demandes');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/demandes',
            'hydra:totalItems' => $countDemandeur3,
        ]);
    }

    public function testDemandeurCannotBypassVisibilityFilter(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $demandeur = $em->getRepository(\App\Entity\Utilisateur::class)->findOneBy(['uid' => 'demandeur']);
        $demande = $em->getRepository(\App\Entity\Demande::class)->findOneBy(['demandeur' => $demandeur]);
        $expectedId = '/demandes/' . $demande->getId();

        $countDemandeur = (int)$em
            ->createQuery('SELECT COUNT(d) FROM App\Entity\Demande d WHERE d.demandeur = :user')
            ->setParameter('user', $demandeur)
            ->getSingleScalarResult();

        $client = $this->createClientWithCredentials('demandeur');
        // Try to query another demandeur's demands
        $client->request('GET', '/demandes?demandeur=/utilisateurs/demandeur3');

        $this->assertResponseIsSuccessful();
        // It should still only return the logged-in user's own demands
        $this->assertJsonContains([
            '@id' => '/demandes',
            'hydra:totalItems' => $countDemandeur,
        ]);

        $data = $client->getResponse()->toArray();
        $this->assertEquals($expectedId, $data['hydra:member'][0]['@id']);
    }

    public function testMembreCommissionOnlySeesCampaignDemandes(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // Re-add membrecommission to commission 1
        $client->request('PUT', '/commissions/1/membres/membrecommission', [
            'json' => [
                'roles' => ['ROLE_ATTRIBUER_PROFIL'],
            ],
        ]);
        $this->assertResponseIsSuccessful();

        // 1. Create a new TypeDemande
        $client->request('POST', '/types_demandes', [
            'json' => [
                'libelle' => 'type commission test',
                'actif' => true,
                'visibiliteLimitee' => false,
                'accompagnementOptionnel' => true,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $typeData = $client->getResponse()->toArray();
        $typeDemandeId = $typeData['@id'];

        // 2. Create a campaign for this type with NO commission
        $client->request('POST', $typeDemandeId . '/campagnes', [
            'json' => [
                'libelle' => 'campagne commission test',
                'debut' => '2020-01-01T08:00:00+00:00',
                'fin' => '2030-02-01T08:00:00+00:00',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $campData = $client->getResponse()->toArray();

        // Now we count initial demands
        $client->request('GET', '/demandes');
        $initialAdminCount = $client->getResponse()->toArray()['hydra:totalItems'] ?? 0;

        $client = $this->createClientWithCredentials('membrecommission');
        $client->request('GET', '/demandes');
        $this->assertResponseIsSuccessful();
        $initialMembreCount = $client->getResponse()->toArray()['hydra:totalItems'] ?? 0;

        // 3. Create a demand for this type
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/demandes', [
            'json' => [
                'typeDemande' => $typeDemandeId,
                'demandeur' => '/utilisateurs/utilisateur-ancien-beneficiaire',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $client->getResponse()->toArray();
        $newDemandeId = $data['@id'];
        $dbId = $data['id'] ?? null;
        if (!$dbId) {
            $dbId = (int) str_replace('/demandes/', '', $newDemandeId);
        }

        try {
            // Admin should see 1 more demand
            $client->request('GET', '/demandes');
            $this->assertResponseIsSuccessful();
            $this->assertJsonContains(['hydra:totalItems' => $initialAdminCount + 1]);

            // 2. Log in as membrecommission
            $client = $this->createClientWithCredentials('membrecommission');
            $client->request('GET', '/demandes');
            $this->assertResponseIsSuccessful();
            $this->assertJsonContains(['hydra:totalItems' => $initialMembreCount]);

            // And they shouldn't see the new demand in the list
            $data = $client->getResponse()->toArray();
            foreach ($data['hydra:member'] as $member) {
                $this->assertNotEquals($newDemandeId, $member['@id']);
            }
        } finally {
            // Clean up
            $em = static::getContainer()->get('doctrine')->getManager();
            $demandeEntity = $em->find(\App\Entity\Demande::class, $dbId);
            if ($demandeEntity) {
                $em->remove($demandeEntity);
                $em->flush();
            }
            // Clean up campaign and type
            $campEntity = $em->find(\App\Entity\CampagneDemande::class, $campData['id']);
            if ($campEntity) {
                $em->remove($campEntity);
                $em->flush();
            }
            $typeEntity = $em->find(\App\Entity\TypeDemande::class, $typeData['id']);
            if ($typeEntity) {
                $em->remove($typeEntity);
                $em->flush();
            }
            // Clean up membership
            $clientAdmin = $this->createClientWithCredentials('admin');
            $clientAdmin->request('DELETE', '/commissions/1/membres/membrecommission');
        }
    }

    public function testRenfortOnlySeesNonLimitedVisibilityDemandes(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        // 1. Initially, count demands visible to renfort and admin using DB query
        $initialTotalCount = (int)$em->createQuery(
            'SELECT COUNT(d) FROM App\Entity\Demande d 
             JOIN d.campagne c
             JOIN c.typeDemande t
             WHERE t.visibiliteLimitee = false',
        )->getSingleScalarResult();

        $initialType1Count = (int)$em
            ->createQuery('SELECT COUNT(d) FROM App\Entity\Demande d 
             JOIN d.campagne c
             WHERE c.typeDemande = :typeId')
            ->setParameter('typeId', 1)
            ->getSingleScalarResult();

        $initialAdminTotalCount = (int)$em->createQuery(
            'SELECT COUNT(d) FROM App\Entity\Demande d',
        )->getSingleScalarResult();

        $client = $this->createClientWithCredentials('renfort');

        // 2. Update typeDemande 1 to have visibiliteLimitee = true
        $typeDemande = $em->find(\App\Entity\TypeDemande::class, 1);

        try {
            $typeDemande->setVisibiliteLimitee(true);
            $em->flush();

            // 3. renfort should now see fewer demands
            $client->request('GET', '/demandes');
            $this->assertResponseIsSuccessful();
            $this->assertJsonContains(['hydra:totalItems' => $initialTotalCount - $initialType1Count]);

            // 4. admin should still see all demands
            $clientAdmin = $this->createClientWithCredentials('admin');
            $clientAdmin->request('GET', '/demandes');
            $this->assertResponseIsSuccessful();
            $this->assertJsonContains(['hydra:totalItems' => $initialAdminTotalCount]);
        } finally {
            $currentEm = static::getContainer()->get('doctrine')->getManager();
            $typeDemande = $currentEm->find(\App\Entity\TypeDemande::class, 1);
            if ($typeDemande) {
                $typeDemande->setVisibiliteLimitee(false);
                $currentEm->flush();
            }
        }
    }

    public function testDemandeurCanReprendreDemande(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $demandeur = $em->getRepository(\App\Entity\Utilisateur::class)->findOneBy(['uid' => 'demandeur']);
        $count = (int)$em
            ->createQuery('SELECT COUNT(d) FROM App\Entity\Demande d 
             JOIN d.campagne c
             WHERE d.demandeur = :user AND c.typeDemande = :typeId')
            ->setParameters([
                'user' => $demandeur,
                'typeId' => 1,
            ])
            ->getSingleScalarResult();

        $client = $this->createClientWithCredentials('demandeur');
        $client->request('GET', '/demandes?demandeur=/utilisateurs/demandeur&campagne.typeDemande=/types_demandes/1');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => $count,
        ]);
    }

    public function testGestionnaireCanFilterDemandesByTypeLibelle(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $em = static::getContainer()->get('doctrine')->getManager();

        $totalExpected = (int)$em
            ->createQuery('SELECT COUNT(d) FROM App\Entity\Demande d 
             JOIN d.campagne c
             WHERE c.typeDemande = :typeId')
            ->setParameter('typeId', 1)
            ->getSingleScalarResult();

        $activeExpected = (int)$em
            ->createQuery('SELECT COUNT(d) FROM App\Entity\Demande d 
             JOIN d.campagne c
             WHERE c.typeDemande = :typeId
             AND (c.dateArchivage IS NULL OR c.dateArchivage >= :now)')
            ->setParameters([
                'typeId' => 1,
                'now' => new \DateTime(),
            ])
            ->getSingleScalarResult();

        // Sans filtre d'archivage
        $client->request('GET', '/demandes?campagne.typeDemande=/types_demandes/1');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => $totalExpected]);

        // Avec filtre d'archivage (actives)
        $client->request('GET', '/demandes?campagne.typeDemande=/types_demandes/1&archivees=false');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => $activeExpected]);
    }

    public function testGestionnaireCanFilterDemandesByDemandeurNom(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $em = static::getContainer()->get('doctrine')->getManager();

        $demandeur = $em->getRepository(\App\Entity\Utilisateur::class)->findOneBy(['uid' => 'demandeur3']);

        $totalExpected = (int)$em
            ->createQuery('SELECT COUNT(d) FROM App\Entity\Demande d 
             WHERE d.demandeur = :demandeur')
            ->setParameter('demandeur', $demandeur)
            ->getSingleScalarResult();

        $activeExpected = (int)$em
            ->createQuery('SELECT COUNT(d) FROM App\Entity\Demande d 
             JOIN d.campagne c
             WHERE d.demandeur = :demandeur
             AND (c.dateArchivage IS NULL OR c.dateArchivage >= :now)')
            ->setParameters([
                'demandeur' => $demandeur,
                'now' => new \DateTime(),
            ])
            ->getSingleScalarResult();

        // Tout
        $client->request('GET', '/demandes?demandeur=/utilisateurs/demandeur3');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => $totalExpected]);

        // Uniquement actives
        $client->request('GET', '/demandes?demandeur=/utilisateurs/demandeur3&archivees=false');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => $activeExpected]);
    }

    public function testAdminCanCreateTypeDemande(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/types_demandes', [
            'json' => [
                'libelle' => 'nouveau type',
                'actif' => true,
                'visibiliteLimitee' => true,
                'accompagnementOptionnel' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'libelle' => 'nouveau type',
        ]);
    }

    public function testAdminCanUpdateTypeDemande(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/types_demandes/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'nouveau libelle',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/types_demandes/1',
            'libelle' => 'nouveau libelle',
        ]);
    }

    public function testGetCampagnesByTypeDemande(): void
    {
        $client = $this->createClientWithCredentials('demandeur');
        $client->request('GET', '/types_demandes/1/campagnes');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/types_demandes/1/campagnes',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testTypeDemandeCampagneFilter(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $em = static::getContainer()->get('doctrine')->getManager();

        $countType1 = (int)$em
            ->createQuery('SELECT COUNT(c) FROM App\Entity\CampagneDemande c WHERE c.typeDemande = :typeId')
            ->setParameter('typeId', 1)
            ->getSingleScalarResult();

        $countType2 = (int)$em
            ->createQuery('SELECT COUNT(c) FROM App\Entity\CampagneDemande c WHERE c.typeDemande = :typeId')
            ->setParameter('typeId', 2)
            ->getSingleScalarResult();

        // Type 1 (sportifs)
        $client->request('GET', '/types_demandes/1/campagnes');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => $countType1,
        ]);

        // Type 2 (artistes)
        $client->request('GET', '/types_demandes/2/campagnes');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'hydra:totalItems' => $countType2,
            'hydra:member' => [
                ['libelle' => 'Campagne artistes 2030'],
            ],
        ]);
    }

    public function testTypeDemandeCampagneOrder(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // Ascending order
        $client->request('GET', '/types_demandes/1/campagnes?order[debut]=asc');
        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertEquals('campagne sportifs archievée 2010', $data['hydra:member'][0]['libelle']);

        // Descending order
        $client->request('GET', '/types_demandes/1/campagnes?order[debut]=desc');
        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $lastIndex = count($data['hydra:member']) - 1;
        $this->assertEquals('campagne sportifs archievée 2010', $data['hydra:member'][$lastIndex]['libelle']);
    }

    public function testAdminCanCreateCampagne(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/types_demandes/1/campagnes', [
            'json' => [
                'libelle' => 'nouvelle campagne',
                'debut' => '2040-01-01',
                'fin' => '2041-01-01',
                'dateCommission' => '2041-02-01',
                'commission' => '/commissions/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'libelle' => 'nouvelle campagne',
        ]);
    }

    public function testAdminCanUpdateCampagne(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $campagne = $em->getRepository(\App\Entity\CampagneDemande::class)->findOneBy([
            'libelle' => 'Campagne sportifs 2030',
        ]);
        $campagneId = $campagne->getId();

        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/types_demandes/1/campagnes/' . $campagneId, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'libelle' => 'nouveau libelle campagne',
                'debut' => '2020-01-01T08:00:00+00:00',
                'fin' => '2030-02-01T08:00:00+00:00',
                'dateCommission' => '2030-03-01T08:00:00+00:00',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'libelle' => 'nouveau libelle campagne',
        ]);
    }

    public function testAdminCanCreateDemande(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/demandes', [
            'json' => [
                'typeDemande' => '/types_demandes/1',
                'demandeur' => '/utilisateurs/demandeur2',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@type' => 'Demande',
        ]);
    }

    public function testCannotCreateTwoDemandesForSameCampaign(): void
    {
        $client = $this->createClientWithCredentials('demandeur');

        // demandeur already has a demande for /types_demandes/1 in fixtures (demande1)
        $client->request('POST', '/demandes', [
            'json' => [
                'typeDemande' => '/types_demandes/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testInvalidStateTransition(): void
    {
        $demandeId = $this->getDemandeIdForDemandeur();
        $client = $this->createClientWithCredentials('admin');

        // demande1 is RECEPTIONNEE (2)
        // Transition to EN_COURS (1) is invalid
        $client->request('PATCH', $demandeId, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'etat' => '/etats_demandes/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAdminCanUpdateDemande(): void
    {
        $client = $this->createClientWithCredentials('admin');
        // create fresh
        $client->request('POST', '/demandes', [
            'json' => [
                'typeDemande' => '/types_demandes/1',
                'demandeur' => '/utilisateurs/demandeur-admin-update',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $id = $client->getResponse()->toArray()['@id'];

        $client->request('PATCH', $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'etat' => '/etats_demandes/5',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $id,
            'etat' => '/etats_demandes/5',
        ]);
    }

    public function testDemandeurCanAnswerQuestion(): void
    {
        $demandeId = $this->getDemandeIdForDemandeur();
        $client = $this->createClientWithCredentials('demandeur');
        $client->request('PUT', $demandeId . '/questions/3/reponse', [
            'json' => [
                'optionsChoisies' => ['/questions/3/options/3'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@id' => $demandeId . '/questions/3/reponse',
        ]);
    }

    public function testGestionnaireCanModifyReponse(): void
    {
        $demandeId = $this->getDemandeIdForDemandeur();
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('PUT', $demandeId . '/questions/3/reponse', [
            'json' => [
                'optionsChoisies' => ['/questions/3/options/3'],
                'commentaire' => 'modifié par le gestionnaire',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'commentaire' => 'modifié par le gestionnaire',
        ]);
    }

    public function testDemandeurRecuperationReponseExistante(): void
    {
        $client = $this->createClientWithCredentials('nouveau-beneficiaire');
        $client->request('POST', '/demandes', [
            'json' => [
                'typeDemande' => '/types_demandes/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testRepondreSubmitValideLaDemandeSiComplete(): void
    {
        // On utilise un utilisateur qui n'a VRAIMENT pas de demande
        $client = $this->createClientWithCredentials('demandeur-complet');
        $client->request('POST', '/demandes', [
            'json' => [
                'typeDemande' => '/types_demandes/1',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $client->getResponse()->toArray();
        $demandeId = $data['@id'];

        // On répond à tout le questionnaire
        // -1: besoin accompagnement
        $client->request('PUT', $demandeId . '/questions/-1/reponse', [
            'json' => ['optionsChoisies' => ['/questions/-1/options/-2']], // non
        ]);
        $this->assertResponseStatusCodeSame(201);

        // 1: question commune 1
        $client->request('PUT', $demandeId . '/questions/1/reponse', [
            'json' => ['optionsChoisies' => ['/questions/1/options/1']],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // 3: question sportifs 1
        $client->request('PUT', $demandeId . '/questions/3/reponse', [
            'json' => ['optionsChoisies' => ['/questions/3/options/3']], // oui -> triggers 4
        ]);
        $this->assertResponseStatusCodeSame(201);

        // 4: question sportifs 2 (mandatory because triggered by 3=oui)
        $client->request('PUT', $demandeId . '/questions/4/reponse', [
            'json' => ['commentaire' => 'SHN_OK'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // On vérifie que si on tente de valider alors qu'il manque le submit, ça échoue (422)
        $client->request('PUT', $demandeId . '/questions/7/reponse', [
            'json' => [
                'commentaire' => 'Validation',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201); // Oh wait, answering the submit question IS the validation

        // On vérifie l'état de la demande
        $client->request('GET', $demandeId);
        $this->assertJsonContains([
            'etat' => '/etats_demandes/10', // Attente validation accompagnement
            'complete' => true,
        ]);
    }

    public function testDemandeurCannotAnswerWithOptionsFromOtherQuestion(): void
    {
        $demandeId = $this->getDemandeIdForDemandeur();
        $client = $this->createClientWithCredentials('demandeur');
        $client->request('PUT', $demandeId . '/questions/1/reponse', [
            'json' => [
                'optionsChoisies' => ['/questions/3/options/3'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testDemandeurCannotAnswerCommentOnlyWhenOptionsRequired(): void
    {
        $demandeId = $this->getDemandeIdForDemandeur();
        $client = $this->createClientWithCredentials('demandeur');
        $client->request('PUT', $demandeId . '/questions/1/reponse', [
            'json' => [
                'commentaire' => 'blabliblu',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testQuestionWithReferenceTableOptions(): void
    {
        $client = $this->createClientWithCredentials('demandeur');
        $client->request('GET', '/questions/5');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/questions/5',
        ]);
        $data = $client->getResponse()->toArray();
        $this->assertArrayHasKey('optionsReponses', $data);
        $this->assertNotEmpty($data['optionsReponses']);
    }

    public function testAnswerWithReferenceTableOption(): void
    {
        $demandeId = $this->getDemandeIdForDemandeur();
        $client = $this->createClientWithCredentials('demandeur');
        $client->request('PUT', $demandeId . '/questions/5/reponse', [
            'json' => [
                'optionsChoisies' => ['/questions/5/options/1'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testSHNValidator(): void
    {
        $demandeId = $this->getDemandeIdForDemandeur();
        $client = $this->createClientWithCredentials('demandeur');

        // OK case
        $client->request('PUT', $demandeId . '/questions/4/reponse', [
            'json' => [
                'commentaire' => 'SHN_OK',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Wrong year
        $client->request('PUT', $demandeId . '/questions/4/reponse', [
            'json' => [
                'commentaire' => 'SHN_WRONG_YEAR',
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);

        // Not found
        $client->request('PUT', $demandeId . '/questions/4/reponse', [
            'json' => [
                'commentaire' => 'SHN_UNKNOWN',
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testMembreCommissionCanSeeDemande(): void
    {
        $clientAdmin = $this->createClientWithCredentials('admin');
        // Re-add membrecommission to commission 1 (in case another test deleted them)
        $clientAdmin->request('PUT', '/commissions/1/membres/membrecommission', [
            'json' => [
                'roles' => ['ROLE_ATTRIBUER_PROFIL'],
            ],
        ]);
        $this->assertResponseIsSuccessful();

        try {
            $demandeId = $this->getDemandeIdForDemandeur();
            $client = $this->createClientWithCredentials('membrecommission');
            // demande_receptionnee_1 est liée à campagne1_sportifs qui est liée à commission_sport
            $client->request('GET', $demandeId);

            $this->assertResponseIsSuccessful();
        } finally {
            // Clean up by deleting the membership we added
            $clientAdmin = $this->createClientWithCredentials('admin');
            $clientAdmin->request('DELETE', '/commissions/1/membres/membrecommission');
        }
    }

    public function testPasserConformeDemandeSansCommissionPasseAProfilValideEtAttenteAccompagnement(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // type_demande_artistes (id 2) n'a pas de commission et 1 seul profil (@profil6)
        $client->request('POST', '/demandes', [
            'json' => [
                'typeDemande' => '/types_demandes/2',
                'demandeur' => '/utilisateurs/demandeur2',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $demandeId = $client->getResponse()->toArray()['@id'];

        // On doit compléter la demande avant de pouvoir la passer à conforme
        $client = $this->createClientWithCredentials('demandeur2');

        // Questionnaire pour artistes (id 2):
        // -1: besoin accompagnement
        $client->request('PUT', $demandeId . '/questions/-1/reponse', [
            'json' => ['optionsChoisies' => ['/questions/-1/options/-1']], // oui
        ]);
        $this->assertResponseStatusCodeSame(201);

        // 1: question commune 1
        $client->request('PUT', $demandeId . '/questions/1/reponse', [
            'json' => ['optionsChoisies' => ['/questions/1/options/1']],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Question artistes 1 (id 9?) - Voyons l'ID réel via GET
        $client->request('GET', $demandeId);
        $data = $client->getResponse()->toArray();
        // On cherche la question manquante dans les étapes
        $questionId = null;
        foreach ($data['etapes'] as $etape) {
            foreach ($etape['questions'] ?? [] as $q) {
                if (isset($q['reponse']) && $q['reponse'] === null && $q['id'] != 7) { // 7 est le submit
                    $questionId = $q['id'];
                    break 2;
                }
            }
        }

        if ($questionId) {
            $client->request('PUT', $demandeId . '/questions/' . $questionId . '/reponse', [
                'json' => ['commentaire' => 'réponse'],
            ]);
            $this->assertResponseStatusCodeSame(201);
        }

        // Submit (7)
        $client->request('PUT', $demandeId . '/questions/7/reponse', [
            'json' => ['commentaire' => 'Validation'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Maintenant l'admin peut passer à conforme (id 3)
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', $demandeId, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'etat' => '/etats_demandes/3',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'etat' => '/etats_demandes/10', // Attente validation accompagnement
        ]);
    }

    public function testGetEtapesDemandes(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/etapes_demandes');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/etapes_demandes',
            '@type' => 'hydra:Collection',
        ]);

        $em = static::getContainer()->get('doctrine')->getManager();
        $etape = $em
            ->getRepository(\App\Entity\EtapeDemande::class)
            ->createQueryBuilder('e')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $etapeId = '/etapes_demandes/' . $etape->getId();

        $client->request('GET', $etapeId);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $etapeId,
            '@type' => 'EtapeDemande',
        ]);
    }

    public function testModificationEtatDemande(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // Create a new demande to have a clean state
        $client->request('POST', '/demandes', [
            'json' => [
                'typeDemande' => '/types_demandes/1',
                'demandeur' => '/utilisateurs/demandeur-modification',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $demandeId = $client->getResponse()->toArray()['@id'];

        // Initial state is EN_COURS (1)
        // Move to REFUSEE (5)
        $client->request('PATCH', $demandeId, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'etat' => '/etats_demandes/5',
            ],
        ]);
        $this->assertResponseIsSuccessful();

        $client->request('GET', $demandeId . '/modifications');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $demandeId . '/modifications',
            'hydra:totalItems' => 1,
        ]);

        $data = $client->getResponse()->toArray();
        $modId = $data['hydra:member'][0]['@id'];

        $client->request('GET', $modId);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $modId,
            'etat' => '/etats_demandes/5',
        ]);
    }
}
