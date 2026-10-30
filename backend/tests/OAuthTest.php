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

use App\Service\OAuthService;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class OAuthTest extends ApiTestCaseCustom
{
    /**
     * Vérifie la gestion et la validation du paramètre state dans le flux OAuth
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

        $oauthService->getAccessToken(
            new Request(),
            'https://attacker.com/callback',
        );
    }

    /**
     * Vérifie que la route /connect/oauth/logout efface le cookie d'authentification.
     */
    public function testOAuthLogoutClearsCookie(): void
    {
        $client = static::createClient();
        $client->request('POST', '/connect/oauth/logout');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $headers = $client->getResponse()->getHeaders();
        $this->assertArrayHasKey('set-cookie', $headers);
        $setCookieHeaders = implode(';;', $headers['set-cookie']);
        $this->assertStringContainsString('deleted', $setCookieHeaders);
    }
}
