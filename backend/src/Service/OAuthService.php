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

namespace App\Service;

use DateTime;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnexpectedValueException;

class OAuthService
{
    private array $options;

    public function __construct(
        $clientId,
        #[SensitiveParameter]
        $clientSecret,
        $redirectUri,
        $urlAuthorize,
        $urlAccessToken,
        $urlResourceOwnerDetails,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly bool $enablePkce = true,
        private readonly ?string $allowedRedirectUris = null,
        private readonly ?string $frontUrl = null,
        private readonly string $resourceOwnerId = 'id',
        private readonly bool $stripUserRealm = false,
        private readonly ?string $scopes = null,
    ) {
        $this->options = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
            'urlAuthorize' => $urlAuthorize,
            'urlAccessToken' => $urlAccessToken,
            'urlResourceOwnerDetails' => $urlResourceOwnerDetails,
            'responseResourceOwnerId' => $resourceOwnerId,
        ];
        if (!empty($this->scopes)) {
            $this->options['scopes'] = array_filter(array_map('trim', explode(' ', $this->scopes)));
        }
    }

    /**
     * @param array $options
     * @return GenericProvider
     */
    private function getProvider(array $options): GenericProvider
    {
        return new GenericProvider($options);
    }

    /**
     * @return array<string>
     */
    public function getAllowedRedirectUris(): array
    {
        $allowed = [
            $this->options['redirectUri'],
            $this->urlGenerator->generate('connect_oauth_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->urlGenerator->generate('connect_oauth_accesstoken', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        if (!empty($this->allowedRedirectUris)) {
            $customUris = array_filter(array_map('trim', explode(',', $this->allowedRedirectUris)));
            $allowed = array_merge($allowed, $customUris);
        } elseif (!empty($this->frontUrl)) {
            $allowed[] = rtrim($this->frontUrl, '/') . '/callback';
        }

        return array_unique($allowed);
    }

    /**
     * @param string $redirectUri
     * @return void
     * @throws UnexpectedValueException
     */
    private function validateRedirectUri(string $redirectUri): void
    {
        if (!in_array($redirectUri, $this->getAllowedRedirectUris(), true)) {
            throw new UnexpectedValueException('Invalid redirect URI');
        }
    }

    /**
     * Échange un code d'autorisation contre un AccessToken (support PKCE).
     *
     * @param string $code
     * @param string|null $codeVerifier
     * @param string|null $redirectUri
     * @return AccessToken
     * @throws IdentityProviderException
     * @throws UnexpectedValueException
     */
    public function exchangeCodeForToken(
        string $code,
        ?string $codeVerifier = null,
        ?string $redirectUri = null,
    ): AccessToken {
        $redirect = $redirectUri ?? $this->options['redirectUri'];
        $this->validateRedirectUri($redirect);

        $options = $this->options;
        $options['redirectUri'] = $redirect;

        if ($this->enablePkce && !empty($codeVerifier)) {
            $options['pkceMethod'] = GenericProvider::PKCE_METHOD_S256;
        }

        $provider = $this->getProvider($options);

        if ($this->enablePkce && !empty($codeVerifier)) {
            $provider->setPkceCode($codeVerifier);
        }

        return $provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);
    }

    /**
     * Extrait et normalise l'identifiant utilisateur depuis le ResourceOwner (Issue #165).
     *
     * @param ResourceOwnerInterface $resourceOwner
     * @return string
     * @throws UnexpectedValueException
     */
    public function extractResourceOwnerUid(ResourceOwnerInterface $resourceOwner): string
    {
        $data = $resourceOwner->toArray();

        // 1. Clé configurée prioritaire
        $uid = $data[$this->resourceOwnerId] ?? null;

        // 2. Fallbacks standards OIDC / CAS à la racine
        if (empty($uid)) {
            foreach (['id', 'sub', 'preferred_username', 'uid'] as $candidate) {
                if (!empty($data[$candidate])) {
                    $uid = $data[$candidate];
                    break;
                }
            }
        }

        // 3. Fallback dans un sous-tableau "attributes" (fréquent avec CAS OAuth)
        if (empty($uid) && isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ([$this->resourceOwnerId, 'id', 'sub', 'preferred_username', 'uid'] as $candidate) {
                if (!empty($data['attributes'][$candidate])) {
                    $candidateVal = $data['attributes'][$candidate];
                    $uid = is_array($candidateVal) ? $candidateVal[0] ?? null : $candidateVal;
                    if (!empty($uid)) {
                        break;
                    }
                }
            }
        }

        if (empty($uid) || !is_string($uid)) {
            throw new UnexpectedValueException(sprintf(
                'Unable to extract user identifier from OAuth resource owner. Expected key "%s", available keys: %s',
                $this->resourceOwnerId,
                implode(', ', array_keys($data)),
            ));
        }

        // 4. Normalisation de l'identifiant scoped (ex: login@domaine.fr -> login)
        if ($this->stripUserRealm && str_contains($uid, '@')) {
            $uid = explode('@', $uid, 2)[0];
        }

        return $uid;
    }

    /**
     * @param Request $request
     * @param string|null $redirectUrl
     * @param string|null $cookieDomain
     * @return string|RedirectResponse
     * @throws IdentityProviderException
     */
    public function getAccessToken(
        Request $request,
        ?string $redirectUrl = null,
        ?string $cookieDomain = null,
    ): string|AccessToken|RedirectResponse {
        $options = $this->options;
        if (null !== $redirectUrl) {
            $this->validateRedirectUri($redirectUrl);
            $options['redirectUri'] = $redirectUrl;
        }

        if ($this->enablePkce) {
            $options['pkceMethod'] = GenericProvider::PKCE_METHOD_S256;
        }

        $provider = $this->getProvider($options);

        $code = $request->query->get('code');
        $state = $request->query->get('state');

        if (null === $code) {
            $authorizationUrl = $provider->getAuthorizationUrl();
            $state = $provider->getState();
            $cookieDomain = empty($cookieDomain) ? null : $cookieDomain;

            $response = new RedirectResponse($authorizationUrl);
            $response->headers->setCookie(Cookie::create(
                'oauth_state',
                $state,
                new DateTime()->modify('+ 10 minutes'),
                '/',
                $cookieDomain,
                $request->isSecure(), // Secure dynamically (allows local HTTP dev)
                true, // HttpOnly
                false,
                Cookie::SAMESITE_LAX,
            ));

            if ($this->enablePkce && null !== $provider->getPkceCode()) {
                $response->headers->setCookie(Cookie::create(
                    'oauth_code_verifier',
                    $provider->getPkceCode(),
                    new DateTime()->modify('+ 10 minutes'),
                    '/',
                    $cookieDomain,
                    $request->isSecure(),
                    true,
                    false,
                    Cookie::SAMESITE_LAX,
                ));
            }

            return $response;
        }

        $cookieState = $request->cookies->get('oauth_state');

        if (null === $state || null === $cookieState || !hash_equals($cookieState, $state)) {
            throw new UnexpectedValueException('Invalid state');
        }

        if ($this->enablePkce) {
            $cookieVerifier = $request->cookies->get('oauth_code_verifier');
            if (empty($cookieVerifier)) {
                throw new UnexpectedValueException('Missing PKCE code verifier');
            }
            $provider->setPkceCode($cookieVerifier);
        }

        try {
            // Try to get an access token using the authorization code grant.
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
            $accessToken->getExpires();
            return $accessToken;
        } catch (IdentityProviderException|UnexpectedValueException $e) {
            // Failed to get the access token or user details.
            throw $e;
        }
    }

    /**
     * @param string $accessToken
     * @return ResourceOwnerInterface
     */
    public function getResourceOwnerFromToken(
        #[SensitiveParameter]
        AccessToken|string $accessToken,
    ): ResourceOwnerInterface {
        $accessTokenObj = $accessToken instanceof AccessToken
            ? $accessToken
            : new AccessToken(['access_token' => $accessToken]);

        if ($accessTokenObj instanceof AccessToken) {
            try {
                if ($accessTokenObj->hasExpired()) {
                    throw new ExpiredTokenException('OAuth AccessToken has expired');
                }
            } catch (RuntimeException) {
                // Expiration inconnue : on laisse le provider valider le token.
            }
        }

        try {
            return $this->getProvider($this->options)->getResourceOwner($accessTokenObj);
        } catch (IdentityProviderException $exception) {
            throw new ExpiredTokenException('OAuth AccessToken is expired or invalid', 0, $exception);
        }
    }
}
