<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Service\OAuthService;
use App\Tests\ApiTestCaseCustom;
use League\OAuth2\Client\Provider\GenericResourceOwner;

final class OAuthCookieDomaineTest extends ApiTestCaseCustom
{
    private const string DOMAINE = '.univ.example';

    public function testJwtCookieIsHostOnlyWhenDomainDoesNotCoverHost(): void
    {
        $cookies = $this->connexionDepuis('http://oasis.autre-domaine.example');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('oasis-token=', $cookies);
        self::assertStringNotContainsStringIgnoringCase('domain=', $cookies);
    }

    public function testJwtCookieKeepsDomainWhenItCoversHost(): void
    {
        $cookies = $this->connexionDepuis('http://oasis.univ.example');

        self::assertResponseIsSuccessful();
        self::assertStringContainsStringIgnoringCase('domain=' . self::DOMAINE, $cookies);
    }

    /**
     * Échange un jeton OAuth contre le JWT de l'API, avec le domaine de cookie de ce test.
     *
     * @return string en-têtes Set-Cookie de la réponse
     */
    private function connexionDepuis(string $origine): string
    {
        $env = $_ENV['JWT_COOKIE_DOMAIN'] ?? null;
        $server = $_SERVER['JWT_COOKIE_DOMAIN'] ?? null;
        $_ENV['JWT_COOKIE_DOMAIN'] = $_SERVER['JWT_COOKIE_DOMAIN'] = self::DOMAINE;

        try {
            $client = static::createClient();

            $oauth = $this->createMock(OAuthService::class);
            $oauth->method('getResourceOwnerFromToken')->willReturn(new GenericResourceOwner(['id' => 'admin'], 'id'));
            static::getContainer()->set(OAuthService::class, $oauth);

            $client->request('POST', $origine . '/connect/oauth/token?json=1', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['accessToken' => 'jeton-de-test'], JSON_THROW_ON_ERROR),
            ]);

            return implode(' ', $client->getResponse()->getHeaders(false)['set-cookie'] ?? []);
        } finally {
            if (null === $env) {
                unset($_ENV['JWT_COOKIE_DOMAIN']);
            } else {
                $_ENV['JWT_COOKIE_DOMAIN'] = $env;
            }
            if (null === $server) {
                unset($_SERVER['JWT_COOKIE_DOMAIN']);
            } else {
                $_SERVER['JWT_COOKIE_DOMAIN'] = $server;
            }
        }
    }
}
