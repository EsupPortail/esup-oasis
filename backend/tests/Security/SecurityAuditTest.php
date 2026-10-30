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

namespace App\Tests\Security;

use App\Tests\ApiTestCaseCustom;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SecurityAuditTest extends ApiTestCaseCustom
{
    /**
     * Vérifie que le LDAP résiste aux injections (ne crash pas et ne retourne rien d'inattendu)
     */
    public function testLdapInjectionResistance(): void
    {
        $client = $this->createClientWithCredentials('admin');

        // Tentative d'injection pour bypasser le filtre (ex: fermer le filtre uid et en ouvrir un autre)
        $client->request('GET', '/utilisateurs?term=*)(uid=*');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 0]);

        // Autre tentative
        $client->request('GET', '/utilisateurs?term=nom(prenom');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Vérifie qu'un bénéficiaire ne peut pas créer d'aménagement pour lui-même ou autrui,
     * ni lire l'aménagement d'un autre bénéficiaire en mettant son propre uid dans l'URL (IDOR)
     */
    public function testBeneficiaireCannotCreateAmenagement(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');
        $client->request('POST', '/utilisateurs/beneficiaire/amenagements', [
            'json' => [
                'typeAmenagement' => '/types_amenagements/1',
                'semestre1' => true,
                'semestre2' => true,
                'debut' => '2025-09-01',
                'fin' => '2026-06-30',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);

        // Amenagement 1 appartient à beneficiaire_decision : tentative d'accès via /utilisateurs/beneficiaire/amenagements/1
        $client->request('GET', '/utilisateurs/beneficiaire/amenagements/1');
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Vérifie que seul l'admin peut modifier le référentiel (Chartes, Tags, Paramètres)
     */
    public function testUnauthorizedReferentielModification(): void
    {
        $client = $this->createClientWithCredentials('beneficiaire');

        // Chartes
        $client->request('POST', '/chartes', [
            'json' => ['libelle' => 'Test', 'contenu' => 'Test'],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // Tags
        $client->request('POST', '/tags', [
            'json' => ['libelle' => 'Test', 'categorie' => '/categories_tags/1'],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // Valeurs Paramètres
        $client->request('POST', '/parametres/QUELCONQUE/valeurs', [
            'json' => ['valeur' => 'Test', 'debut' => '2025-01-01'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Vérifie la validation des types de fichiers (MIME vs Extension)
     */
    public function testFileUploadSecurity(): void
    {
        $client = $this->createClientWithCredentials('demandeur2');

        // 1. Incohérence MIME/Extension (JPEG déguisé en PDF)
        $tempFile = tempnam(sys_get_temp_dir(), 'test_sec');
        file_put_contents($tempFile, "\xFF\xD8\xFF\xE0" . str_repeat('A', 100)); // Header JPEG
        $uploadedFile = new UploadedFile($tempFile, 'malicieux.pdf', 'image/jpeg', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['hydra:description' => 'Type de fichier IMAGE incohérent avec l\'extension pdf.']);

        // 2. Type non autorisé
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test_sec2');
        file_put_contents($tempFile2, '{"toto": "titi"}');
        $uploadedFile2 = new UploadedFile($tempFile2, 'script.json', 'application/json', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile2],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['hydra:description' => 'Type de fichier non autorisé : json.']);

        // 3. Tentative de Path Traversal dans le nom du fichier
        $tempFile3 = tempnam(sys_get_temp_dir(), 'test_sec3');
        file_put_contents($tempFile3, 'contenu texte inoffensif');
        $uploadedFile3 = new UploadedFile($tempFile3, '../../../var/log/traversal.txt', 'text/plain', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile3],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $createdId = basename($client->getResponse()->toArray()['@id']);
        $em = static::getContainer()->get('doctrine')->getManager();
        $fichier = $em->getRepository(\App\Entity\Fichier::class)->find($createdId);
        $metadata = $fichier->getMetadata();
        self::assertStringNotContainsString('..', $metadata['nom']);
        self::assertSame(realpath($metadata['base']), dirname(realpath($metadata['cheminComplet'])));
    }

    /**
     * Vérifie la sécurité du state OAuth contre les attaques CSRF
     */
     public function testOAuthStateSecurity(): void
    {
        $client = static::createClient();

        // 1. Démarrer le flux : redirection vers le serveur CAS/OAuth
        $client->request('GET', '/connect/oauth/');
        $this->assertResponseStatusCodeSame(302);

        $headers = $client->getResponse()->getHeaders(false);
        $location = $headers['location'][0] ?? '';
        static::assertStringStartsWith('https://cas.univ.fr/cas/oauth2.0/authorize', $location);

        // Extraire le state généré par le serveur
        $parts = parse_url($location);
        parse_str($parts['query'] ?? '', $queryParams);
        $generatedState = $queryParams['state'] ?? null;
        static::assertNotEmpty($generatedState);

        // 2. Retour de CAS avec un state invalide -> doit être rejeté (CSRF protection)
        $client->request('GET', '/connect/oauth/?code=fake_code&state=bad_state');
        $this->assertResponseStatusCodeSame(500);
        static::assertStringContainsString('Invalid state', $client->getResponse()->getContent(false));

        // 3. Retour de CAS sans state -> doit être rejeté
        $client->request('GET', '/connect/oauth/?code=fake_code');
        $this->assertResponseStatusCodeSame(500);
        static::assertStringContainsString('Invalid state', $client->getResponse()->getContent(false));
    }

    /**
     * Vérifie que le redirect_uri dynamique est validé par rapport à une liste blanche
     */
    public function testOAuthRedirectUriWhitelist(): void
    {
        $oauthService = static::getContainer()->get(\App\Service\OAuthService::class);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid redirect URI');

        $oauthService->getAccessToken(
            new \Symfony\Component\HttpFoundation\Request(),
            'https://attacker.com/callback'
        );
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
    }

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

    /**
     * Vérifie qu'un membre de commission ou un renfort ne peut pas contourner le filtrage
     * des demandes en passant une campagne ou un type de demande non autorisé
     */
    public function testDemandeFilterBypassPrevention(): void
    {
        $client = $this->createClientWithCredentials('membrecommission');

        // Campagne 2 (type_demande 2) n'est pas rattachée à la commission de membrecommission
        $client->request('GET', '/demandes?campagne=/types_demandes/2/campagnes/2');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 0]);

        // Un renfort qui filtre sur un type de demande inexistant/interdit doit obtenir 0 résultat
        $tokenRenfort = $this->getTokenForUser('renfort');
        $client->request('GET', '/demandes?campagne.typeDemande=/types_demandes/999', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenRenfort],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['hydra:totalItems' => 0]);
    }

    /**
     * Vérifie les restrictions de droits dans les Voters de modification :
     * - Un renfort ne peut pas créer d'aménagement d'examen
     * - Un intervenant ne peut pas modifier ses dates de contrat ou compétences
     */
    public function testVoterModificationRestrictions(): void
    {
        $client = $this->createClientWithCredentials('renfort');
        $tokenRenfort = $this->getTokenForUser('renfort');
        $tokenIntervenant = $this->getTokenForUser('intervenant');

        // 1. Un renfort tente de créer un aménagement d'examen (type_amenagement 1 : examens=true, aideHumaine=false)
        $client->request('POST', '/utilisateurs/beneficiaire/amenagements', [
            'headers' => ['Authorization' => 'Bearer ' . $tokenRenfort],
            'json' => [
                'typeAmenagement' => '/types_amenagements/1',
                'semestre1' => true,
                'semestre2' => true,
                'debut' => '2025-09-01',
                'fin' => '2026-06-30',
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // 2. Un intervenant tente de modifier ses compétences ou sa date de fin de contrat
        $client->request('PATCH', '/utilisateurs/intervenant', [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokenIntervenant,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'intervenantFin' => '2099-12-31',
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }
}
