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

use ApiPlatform\Symfony\Bundle\Test\Response;
use App\Entity\Utilisateur;
use DateTime;

class UtilisateursTest extends ApiTestCaseCustom
{
    public function testRenfortCanListPlanificateurs(): void
    {
        $client = $this->createClientWithCredentials('renfort');
        $client->request('GET', '/roles/ROLE_PLANIFICATEUR/utilisateurs');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/roles/ROLE_PLANIFICATEUR/utilisateurs',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testRenfortCanListIntervenants(): void
    {
        $client = $this->createClientWithCredentials('renfort');
        $client->request('GET', '/intervenants');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/intervenants',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGestionnaireCanListIntervenants(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/intervenants');

        $this->assertResponseIsSuccessful();
    }

    public function testSearchMultipleFields(): void
    {
        $client = $this->createClientWithCredentials('renfort');

        // On compte le nombre de bénéficiaires en base (ceux qui matchent "benef")
        $em = static::getContainer()->get('doctrine')->getManager();
        $count = $em
            ->createQuery('SELECT COUNT(DISTINCT u.uid) FROM App\Entity\Utilisateur u 
                                   JOIN u.beneficiaires b 
                                   WHERE (u.nom LIKE :search OR u.prenom LIKE :search OR u.uid LIKE :search OR u.email LIKE :search)
                                   AND :now >= b.debut AND (:now < b.fin OR b.fin IS NULL)')
            ->setParameter('search', '%benef%')
            ->setParameter('now', new DateTime())
            ->getSingleScalarResult();

        // Search beneficiaires
        $client->request('GET', '/beneficiaires?recherche=benef');
        $this->assertJsonContains(['hydra:totalItems' => (int) $count]);

        // Search intervenants (intervenant, intervenant2)
        // nouvelintervenant n'a pas encore le rôle dans les fixtures
        $client->request('GET', '/intervenants?recherche=inter');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }

    public function testFilterByRoleAndPaginateAndSort(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/roles/ROLE_PLANIFICATEUR/utilisateurs?page=1&itemsPerPage=7&order[nom]=desc');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertArrayHasKey('hydra:totalItems', $data);
        $this->assertLessThanOrEqual(7, count($data['hydra:member']));
    }

    public function testCommissionMemberHasRole(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/utilisateurs/admin');

        $this->assertResponseIsSuccessful();
        $this->assertContains('ROLE_ADMIN', $client->getResponse()->toArray()['roles']);
    }

    public function testAdminCanAddIntervenant(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/utilisateurs/nouvelintervenant', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'roles' => ['ROLE_INTERVENANT'],
                'typesEvenements' => ['/types_evenements/1'],
                'campus' => ['/campus/1'],
                'competences' => ['/competences/1'],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'uid' => 'nouvelintervenant',
        ]);
        $this->assertContains('ROLE_INTERVENANT', $client->getResponse()->toArray()['roles']);
    }

    public function testAdminCanAddRenfort(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/utilisateurs/nouveaurenfort', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'roles' => ['ROLE_RENFORT'],
                'services' => ['/services/1'],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertContains('ROLE_RENFORT', $data['roles']);
    }

    public function testRenfortLosesRoleIfNoService(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('PATCH', '/utilisateurs/nouveaurenfort', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'roles' => ['ROLE_USER', 'ROLE_PLANIFICATEUR'],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertNotContains('ROLE_RENFORT', $data['roles']);
    }

    public function testUpdateOwnProfile(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('PATCH', '/utilisateurs/gestionnaire', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'emailPerso' => 'toto@tutu.com',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'emailPerso' => 'toto@tutu.com',
        ]);
    }

    public function testUserCannotUpdateOtherProfile(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('PATCH', '/utilisateurs/beneficiaire2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'emailPerso' => 'titi@tutu.com',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanListCurrentBeneficiaires(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/beneficiaires');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertGreaterThanOrEqual(2, $data['hydra:totalItems']);

        $this->assertEquals('/utilisateurs/beneficiaire', $data['hydra:member'][0]['@id']);
        $this->assertArrayHasKey('profils', $data['hydra:member'][0]);
    }

    public function testAdminCanFilterBeneficiairesByDate(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/beneficiaires?filtreBeneficiaire[avant]=2100-01-01');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertGreaterThanOrEqual(2, $data['hydra:totalItems']);

        $total = $data['hydra:totalItems'];

        //ancien-beneficaire a une fin au 31/12/2020
        $client->request('GET', '/beneficiaires?filtreBeneficiaire[apres]=2021-01-01');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertLessThan($total, $data['hydra:totalItems']);
    }

    public function testAdminCanFilterBeneficiairesByProfil(): void
    {
        $client = $this->createClientWithCredentials('admin');

        $client->request('GET', '/beneficiaires?filtreBeneficiaire[avant]=2100-01-01');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertGreaterThanOrEqual(2, $data['hydra:totalItems']);

        $total = $data['hydra:totalItems'];

        $client->request('GET', '/beneficiaires?filtreBeneficiaire[profil]=/profils/1');
        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertLessThan($total, $data['hydra:totalItems']);
    }

    public function testAdminCanAddTagToUser(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/utilisateurs/beneficiaire/tags', [
            'json' => [
                'tag' => '/tags/1',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'tag' => '/tags/1',
        ]);
    }

    public function testAdminCanListUserTags(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/utilisateurs/beneficiaire/tags', [
            'json' => [
                'tag' => '/tags/1',
            ],
        ]);

        $client->request('GET', '/utilisateurs/beneficiaire/tags');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['hydra:member']));
    }

    public function testAdminCanDeleteUserTag(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('POST', '/utilisateurs/beneficiaire/tags', [
            'json' => [
                'tag' => '/tags/1',
            ],
        ]);

        $client->request('DELETE', '/utilisateurs/beneficiaire/tags/1');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testNumeroAnonymeUniquenessAndVoter(): void
    {
        $client = $this->createClientWithCredentials('admin');

        $repo = static::getContainer()->get('doctrine')->getManager()->getRepository(Utilisateur::class);

        $beneficiaire = $repo->findOneBy(['uid' => 'beneficiaire']);
        $beneficiaire->setNumeroAnonyme(null);
        $repo->save($beneficiaire, true);

        $client->request('PATCH', '/utilisateurs/beneficiaire', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'numeroAnonyme' => 87654123,
            ],
        ]);

        $this->assertResponseIsSuccessful();

        $client->request('PATCH', '/utilisateurs/beneficiaire', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'numeroAnonyme' => 22222222,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testSearchLdap(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/utilisateurs?term=Nom');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testFilterBeneficiairesAvecAmenagementEnCours(): void
    {
        $client = $this->createClientWithCredentials('admin');

        $client->request('GET', '/beneficiaires');
        $total = $client->getResponse()->toArray()['hydra:totalItems'];

        $client->request('GET', '/amenagements/utilisateurs?avecAmenagementEnCours=true');
        $this->assertResponseIsSuccessful();
        $this->assertLessThanOrEqual($total, $client->getResponse()->toArray()['hydra:totalItems']);
    }

    public function testFilterBeneficiairesByTag(): void
    {
        $client = $this->createClientWithCredentials('admin');

        $client->request('POST', '/utilisateurs/beneficiaire/tags', [
            'json' => [
                'tag' => '/tags/1',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/beneficiaires?tags[]=/tags/1');
        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();

        $this->assertGreaterThanOrEqual(1, $data['hydra:totalItems']);
    }

    public function testGetPhoto(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/utilisateurs/beneficiaire2/photo', [
            'headers' => ['Accept' => 'image/jpeg'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('image/jpeg', $client->getResponse()->getHeaders()['content-type'][0]);
        $this->assertEquals('dummy_photo_content', $client->getResponse()->getContent());
    }

    public function testGetPhotoForbidden(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/utilisateurs/beneficiaire/photo', [
            'headers' => ['Accept' => 'image/jpeg'],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetInscription(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $client->request('GET', '/inscriptions/1');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Inscription',
            '@id' => '/inscriptions/1',
        ]);
    }

    public function testIntervenantCanSeeBeneficiaryOfTheirEvents(): void
    {
        $client = $this->createClientWithCredentials('intervenant');
        $client->request('GET', '/utilisateurs/beneficiaire');

        $this->assertResponseIsSuccessful();
    }

    public function testIntervenantCannotSeeOtherBeneficiary(): void
    {
        $client = $this->createClientWithCredentials('intervenant2');
        $client->request('GET', '/utilisateurs/beneficiaire2');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIntervenantCanSeeCreatorOfTheirEvents(): void
    {
        $client = $this->createClientWithCredentials('intervenant');
        $client->request('GET', '/utilisateurs/admin');

        $this->assertResponseIsSuccessful();
    }

    public function testGestionnaireCanSeeInfosComplementairesForStudent(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');

        $em = static::getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository(Utilisateur::class);
        $beneficiaire = $repo->findOneBy(['uid' => 'beneficiaire']);
        $beneficiaire->setNumeroEtudiant(12345678);
        $repo->save($beneficiaire, true);

        $client->request('GET', '/utilisateurs/beneficiaire');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertArrayHasKey('infosComplementaires', $data);
        $this->assertArraySubset([['libelle' => 'someKey', 'valeur' => 'someValue']], $data['infosComplementaires']);
    }

    public function testStudentCanSeeTheirOwnInfosComplementaires(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');

        $em = static::getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository(Utilisateur::class);
        $beneficiaire = $repo->findOneBy(['uid' => 'beneficiaire']);
        $beneficiaire->setNumeroEtudiant(12345678);
        $repo->save($beneficiaire, true);

        $client->request('GET', '/utilisateurs/beneficiaire');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertArrayHasKey('infosComplementaires', $data);
        $this->assertArraySubset([['libelle' => 'someKey', 'valeur' => 'someValue']], $data['infosComplementaires']);
    }

    public function testOtherUserCannotSeeInfosComplementaires(): void
    {
        $client = $this->createClientWithCredentials('intervenant');

        $em = static::getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository(Utilisateur::class);
        $beneficiaire = $repo->findOneBy(['uid' => 'beneficiaire']);
        $beneficiaire->setNumeroEtudiant(12345678);
        $repo->save($beneficiaire, true);

        $client->request('GET', '/utilisateurs/beneficiaire');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertArrayNotHasKey('infosComplementaires', $data);
    }

    public function testUserWithoutNumeroEtudiantHasNoInfosComplementaires(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/utilisateurs/admin');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertEmpty($data['infosComplementaires']);
    }

    public function testCollectionPreloadsInfosComplementaires(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');

        $em = static::getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository(Utilisateur::class);
        $beneficiaire = $repo->findOneBy(['uid' => 'beneficiaire']);
        $beneficiaire->setNumeroEtudiant(12345678);
        $repo->save($beneficiaire, true);

        $client->request('GET', '/beneficiaires');

        $this->assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertNotEmpty($data['hydra:member']);

        // Find beneficiaire in the collection
        $beneficiaires = array_filter($data['hydra:member'], fn($u) => $u['uid'] === 'beneficiaire');
        $this->assertNotEmpty($beneficiaires);
        $item = array_values($beneficiaires)[0];
        $this->assertArrayHasKey('infosComplementaires', $item);
        $this->assertArraySubset([['libelle' => 'someKey', 'valeur' => 'someValue']], $item['infosComplementaires']);
    }
}
