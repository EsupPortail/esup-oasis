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

use Symfony\Component\HttpFoundation\File\UploadedFile;

class PiecesJointesTest extends ApiTestCaseCustom
{
    public function testGestionnaireCanListPiecesJointes(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $client->request('GET', '/beneficiaires/beneficiaire/pieces_jointes');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/PieceJointeBeneficiaire',
            '@id' => '/beneficiaires/beneficiaire/pieces_jointes',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testBeneficiaireCanSeeOwnPiecesJointes(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/beneficiaires/beneficiaire/pieces_jointes');

        $this->assertResponseIsSuccessful();
    }

    public function testBeneficiaireCannotSeeOtherPiecesJointes(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('GET', '/beneficiaires/beneficiaire2/pieces_jointes');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testBeneficiaireCannotBypassUidOrMutatePieceJointe(): void
    {
        $tokenGestionnaire = $this->getTokenForUser('gestionnaire');
        $tokenBeneficiaire2 = $this->getTokenForUser('beneficiaire2');
        $client = $this->createClientWithCredentials('gestionnaire');

        // 1. Le gestionnaire crée une PJ pour "beneficiaire"
        $tempFile = tempnam(sys_get_temp_dir(), 'test_pj_idor');
        file_put_contents($tempFile, 'contenu pj beneficiaire 1');
        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile($tempFile, 'pj1.txt', 'text/plain', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokenGestionnaire,
                'Content-Type' => 'multipart/form-data',
            ],
            'extra' => ['files' => ['file' => $uploadedFile]],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $fileIri = $client->getResponse()->toArray()['@id'];

        $client->request('POST', '/beneficiaires/beneficiaire/pieces_jointes', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenGestionnaire],
            'json' => [
                'libelle' => 'PJ confidentielle beneficiaire 1',
                'fichier' => $fileIri,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $pjIri = $client->getResponse()->toArray()['@id'];
        $pjId = basename($pjIri);

        // 2. beneficiaire2 tente d'y accéder en mettant son propre uid dans l'URL (IDOR)
        $client->request('GET', '/beneficiaires/beneficiaire2/pieces_jointes/' . $pjId, [
            'headers' => ['Authorization' => 'Bearer ' . $tokenBeneficiaire2],
        ]);
        $this->assertResponseStatusCodeSame(404);

        // 3. Un gestionnaire ne peut pas supprimer la PJ via le mauvais uid dans l'URL
        $client->request('DELETE', '/beneficiaires/beneficiaire2/pieces_jointes/' . $pjId, [
            'headers' => ['Authorization' => 'Bearer ' . $tokenGestionnaire],
        ]);
        $this->assertResponseStatusCodeSame(404);

        // 4. beneficiaire2 ne peut ni supprimer ni créer de PJ (403)
        $client->request('DELETE', '/beneficiaires/beneficiaire/pieces_jointes/' . $pjId, [
            'headers' => ['Authorization' => 'Bearer ' . $tokenBeneficiaire2],
        ]);
        $this->assertResponseStatusCodeSame(403);

        $client->request('POST', '/beneficiaires/beneficiaire2/pieces_jointes', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenBeneficiaire2],
            'json' => [
                'libelle' => 'Tentative création par bénéficiaire',
                'fichier' => $fileIri,
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testGestionnaireCanUploadAndDeletePieceJointe(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        
        // 1. Upload a file first
        $tempFile = tempnam(sys_get_temp_dir(), 'test_pj');
        file_put_contents($tempFile, 'contenu pj');
        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile($tempFile, 'pj.txt', 'text/plain', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => ['files' => ['file' => $uploadedFile]],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $fileData = $client->getResponse()->toArray();
        $fileIri = $fileData['@id'];

        // 2. Create the PieceJointeBeneficiaire
        $client->request('POST', '/beneficiaires/beneficiaire/pieces_jointes', [
            'json' => [
                'libelle' => 'Ma pièce jointe test',
                'fichier' => $fileIri,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $pjData = $client->getResponse()->toArray();
        $pjIri = $pjData['@id'];
        $this->assertEquals('Ma pièce jointe test', $pjData['libelle']);

        // 2b. GET it to exercise getters
        $client->request('GET', $pjIri);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'libelle' => 'Ma pièce jointe test',
        ]);

        // 3. Delete it
        $client->request('DELETE', $pjIri);
        $this->assertResponseStatusCodeSame(204);

        // 4. Verify it's gone
        $client->request('GET', $pjIri);
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Vérifie qu'un demandeur ne peut pas voler la propriété d'un fichier appartenant à un tiers
     * ni réutiliser un fichier déjà rattaché à une autre ressource
     */
    public function testCannotStealOrReuseFileViaPieceJustificative(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $tokenGestionnaire = $this->getTokenForUser('gestionnaire');
        $tokenDemandeur = $this->getTokenForUser('demandeur');

        // 1. Le gestionnaire téléverse un fichier confidentiel
        $tempVictimFile = tempnam(sys_get_temp_dir(), 'test_victim');
        file_put_contents($tempVictimFile, 'document confidentiel');
        $uploadedVictimFile = new UploadedFile($tempVictimFile, 'confidentiel.txt', 'text/plain', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokenGestionnaire,
                'Content-Type' => 'multipart/form-data',
            ],
            'extra' => [
                'files' => ['file' => $uploadedVictimFile],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $victimData = $client->getResponse()->toArray();
        $victimFileIri = $victimData['@id'];
        $victimDownloadUrl = $victimData['urlContenu'];

        // 2. Un demandeur tente de rattacher ce fichier tiers à sa propre demande
        $em = static::getContainer()->get('doctrine')->getManager();
        $demandeur = $em->getRepository(\App\Entity\Utilisateur::class)->findOneBy(['uid' => 'demandeur']);
        $demande = $em->getRepository(\App\Entity\Demande::class)->findOneBy(['demandeur' => $demandeur]);
        $demandeIri = '/demandes/' . $demande->getId();

        // Tant que la demande est RECEPTIONNEE, le demandeur ne peut pas modifier ses réponses (403)
        $demande->setEtat($em->getRepository(\App\Entity\EtatDemande::class)->find(\App\Entity\EtatDemande::RECEPTIONNEE));
        $em->flush();
        if ($em->getConnection()->isTransactionActive()) {
            $em->getConnection()->commit();
            $em->getConnection()->beginTransaction();
        }

        $client->request('PUT', $demandeIri . '/questions/3/reponse', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenDemandeur],
            'json' => [
                'optionsChoisies' => ['/questions/3/options/3'],
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // Passage de la demande à EN_COURS pour tester la validation de PJ par le demandeur
        $em = static::getContainer()->get('doctrine')->getManager();
        $demande = $em->getRepository(\App\Entity\Demande::class)->find($demande->getId());
        $demande->setEtat($em->getRepository(\App\Entity\EtatDemande::class)->find(\App\Entity\EtatDemande::EN_COURS));
        $em->flush();
        if ($em->getConnection()->isTransactionActive()) {
            $em->getConnection()->commit();
            $em->getConnection()->beginTransaction();
        }

        $client->request('PUT', $demandeIri . '/questions/3/reponse', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenDemandeur],
            'json' => [
                'optionsChoisies' => ['/questions/3/options/3'],
                'piecesJustificatives' => [$victimFileIri],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['hydra:description' => 'piecesJustificatives: Vous ne pouvez pas associer ce fichier.']);

        // 3. Le demandeur ne doit toujours pas pouvoir télécharger le fichier confidentiel
        $client->request('GET', $victimDownloadUrl, [
            'headers' => ['Authorization' => 'Bearer ' . $tokenDemandeur],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // 4. Le demandeur peut en revanche téléverser et attacher son propre fichier
        $tempOwnFile = tempnam(sys_get_temp_dir(), 'test_own');
        file_put_contents($tempOwnFile, 'ma piece justificative');
        $uploadedOwnFile = new UploadedFile($tempOwnFile, 'ma_pj.txt', 'text/plain', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokenDemandeur,
                'Content-Type' => 'multipart/form-data',
            ],
            'extra' => [
                'files' => ['file' => $uploadedOwnFile],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $ownFileIri = $client->getResponse()->toArray()['@id'];

        $client->request('PUT', $demandeIri . '/questions/3/reponse', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenDemandeur],
            'json' => [
                'optionsChoisies' => ['/questions/3/options/3'],
                'piecesJustificatives' => [$ownFileIri],
            ],
        ]);
        $this->assertResponseIsSuccessful();

        // 5. Une mise à jour de la même réponse conservant ce fichier reste autorisée
        $client->request('PUT', $demandeIri . '/questions/3/reponse', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenDemandeur],
            'json' => [
                'optionsChoisies' => ['/questions/3/options/3'],
                'commentaire' => 'mise à jour',
                'piecesJustificatives' => [$ownFileIri],
            ],
        ]);
        $this->assertResponseIsSuccessful();

        // 6. Un fichier déjà rattaché à une ressource ne peut pas être rattaché à une autre ressource
        $client->request('POST', '/beneficiaires/beneficiaire/pieces_jointes', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenGestionnaire],
            'json' => [
                'libelle' => 'Tentative de réutilisation',
                'fichier' => $ownFileIri,
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['hydra:description' => 'fichier: Ce fichier est déjà associé à une autre ressource.']);

        $em = static::getContainer()->get('doctrine')->getManager();
        $demande = $em->getRepository(\App\Entity\Demande::class)->find($demande->getId());
        $demande->setEtat($em->getRepository(\App\Entity\EtatDemande::class)->find(\App\Entity\EtatDemande::RECEPTIONNEE));
        $em->flush();
        if ($em->getConnection()->isTransactionActive()) {
            $em->getConnection()->commit();
            $em->getConnection()->beginTransaction();
        }
    }
}
