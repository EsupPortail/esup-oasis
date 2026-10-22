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

use App\Service\OAuthService;
use App\Tests\ApiTestCaseCustom;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

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
     * Vérifie qu'un bénéficiaire ne peut pas créer d'aménagement pour lui-même ou autrui
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
        $oauthService = static::getContainer()->get(OAuthService::class);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid redirect URI');

        $oauthService->getAccessToken(new Request(), 'https://attacker.com/callback');
    }

    /**
     * Vérifie la présence des paramètres PKCE (code_challenge) et du cookie code_verifier
     */
    public function testOAuthPkceAuthorizationAndMissingVerifier(): void
    {
        $client = static::createClient();

        // 1. Démarrer le flux avec PKCE
        $client->request('GET', '/connect/oauth/');
        $this->assertResponseStatusCodeSame(302);

        $location = $client->getResponse()->getHeaders(false)['location'][0] ?? '';
        $parts = parse_url($location);
        parse_str($parts['query'] ?? '', $queryParams);

        static::assertArrayHasKey('code_challenge', $queryParams);
        static::assertArrayHasKey('code_challenge_method', $queryParams);
        static::assertSame('S256', $queryParams['code_challenge_method']);

        // Vérifier la présence du cookie oauth_code_verifier
        $cookieJar = $client->getCookieJar();
        $codeVerifierCookie = $cookieJar->get('oauth_code_verifier');
        $stateCookie = $cookieJar->get('oauth_state');

        static::assertNotNull($codeVerifierCookie, 'Le cookie oauth_code_verifier doit être positionné.');
        static::assertNotNull($stateCookie, 'Le cookie oauth_state doit être positionné.');

        // 2. Retour de l'IdP avec un state valide mais sans le cookie oauth_code_verifier
        $cookieJar->expire('oauth_code_verifier');
        $validState = $stateCookie->getValue();
        $client->request('GET', '/connect/oauth/?code=fake_code&state=' . $validState);
        $this->assertResponseStatusCodeSame(500);
        static::assertStringContainsString('Missing PKCE code verifier', $client->getResponse()->getContent(false));
    }

    /**
     * Vérifie l'extraction intelligente de l'UID (sub, id, preferred_username) et le nettoyage de domaine (Issue #165)
     */
    public function testOAuthResourceOwnerUidExtraction(): void
    {
        $urlGenerator = static::getContainer()->get('router');

        // 1. Cas standard CAS (id)
        $serviceCas = new OAuthService(
            'cid',
            'sec',
            'https://this.app/',
            'http://a',
            'http://t',
            'http://p',
            $urlGenerator,
            true,
            null,
            null,
            'id',
            false,
        );
        $ownerCas = new GenericResourceOwner(['id' => 'user_cas'], 'id');
        static::assertSame('user_cas', $serviceCas->extractResourceOwnerUid($ownerCas));

        // 2. Cas OIDC LemonLDAP / Shibboleth (sub configuré)
        $serviceOidc = new OAuthService(
            'cid',
            'sec',
            'https://this.app/',
            'http://a',
            'http://t',
            'http://p',
            $urlGenerator,
            true,
            null,
            null,
            'sub',
            false,
        );
        $ownerOidc = new GenericResourceOwner(['sub' => 'user_oidc'], 'sub');
        static::assertSame('user_oidc', $serviceOidc->extractResourceOwnerUid($ownerOidc));

        // 3. Cas Fallback automatique (configuré 'id', mais IdP renvoie seulement 'sub')
        $ownerFallback = new GenericResourceOwner([
            'sub' => 'user_sub_only',
            'name' => 'Test',
        ], 'id');
        static::assertSame('user_sub_only', $serviceCas->extractResourceOwnerUid($ownerFallback));

        // 4. Cas Fallback preferred_username
        $ownerPreferred = new GenericResourceOwner([
            'preferred_username' => 'user_pref',
        ], 'id');
        static::assertSame('user_pref', $serviceCas->extractResourceOwnerUid($ownerPreferred));

        // 5. Cas scoped avec nettoyage de realm (login@domaine.fr -> login)
        $serviceScoped = new OAuthService(
            'cid',
            'sec',
            'https://this.app/',
            'http://a',
            'http://t',
            'http://p',
            $urlGenerator,
            true,
            null,
            null,
            'sub',
            true,
        );
        $ownerScoped = new GenericResourceOwner([
            'sub' => 'jdupont@univ-paris.fr',
        ], 'sub');
        static::assertSame('jdupont', $serviceScoped->extractResourceOwnerUid($ownerScoped));

        // 6. Cas où aucune claim d'identifiant n'est présente -> exception explicite
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to extract user identifier from OAuth resource owner');
        $ownerEmpty = new GenericResourceOwner(['email' => 'foo@bar.com'], 'id');
        $serviceCas->extractResourceOwnerUid($ownerEmpty);
    }

    /**
     * Vérifie la validation des redirectUri via FRONT_URL / callback
     */
    public function testOAuthRedirectUriWhitelistFrontUrlFallback(): void
    {
        $urlGenerator = static::getContainer()->get('router');
        $service = new OAuthService(
            'cid',
            'sec',
            'https://this.app/',
            'http://a',
            'http://t',
            'http://p',
            $urlGenerator,
            true,
            null,
            'https://front.univ-test.fr',
            'id',
            false,
        );

        $allowed = $service->getAllowedRedirectUris();
        static::assertContains('https://front.univ-test.fr/callback', $allowed);
        static::assertNotContains('https://malicious.org/callback', $allowed);
    }
}
