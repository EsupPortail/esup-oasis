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
    ) {
        $this->options = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
            'urlAuthorize' => $urlAuthorize,
            'urlAccessToken' => $urlAccessToken,
            'urlResourceOwnerDetails' => $urlResourceOwnerDetails,
        ];
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
     * @param string $redirectUri
     * @return void
     * @throws UnexpectedValueException
     */
    private function validateRedirectUri(string $redirectUri): void
    {
        $allowedUris = [
            $this->options['redirectUri'],
            $this->urlGenerator->generate('connect_oauth_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->urlGenerator->generate('connect_oauth_accesstoken', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        if (!in_array($redirectUri, $allowedUris, true)) {
            throw new UnexpectedValueException('Invalid redirect URI');
        }
    }

    /**
     * @param Request $request
     * @param string|null $redirectUrl
     * @param string|null $cookieDomain
     * @return string|RedirectResponse
     * @throws IdentityProviderException
     */
    public function getAccessToken(Request $request, ?string $redirectUrl = null, ?string $cookieDomain = null): string|RedirectResponse
    {
        if (null !== $redirectUrl) {
            $this->validateRedirectUri($redirectUrl);
            $this->options['redirectUri'] = $redirectUrl;
        }

        $provider = $this->getProvider($this->options);

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
                (new DateTime())->modify('+ 10 minutes'),
                '/',
                $cookieDomain,
                $request->isSecure(), // Secure dynamically (allows local HTTP dev)
                true, // HttpOnly
                false,
                Cookie::SAMESITE_LAX,
            ));

            return $response;
        }

        $cookieState = $request->cookies->get('oauth_state');

        if (null === $state || null === $cookieState || !hash_equals($cookieState, $state)) {
            throw new UnexpectedValueException('Invalid state');
        }

        try {
            // Try to get an access token using the authorization code grant.
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
            $accessToken->getExpires();
            return $accessToken->getToken();
        } catch (IdentityProviderException|UnexpectedValueException $e) {
            // Failed to get the access token or user details.
            throw $e;
        }
    }

    /**
     * @param string $accessToken
     * @return ResourceOwnerInterface
     */
    public function getResourceOwnerFromToken(#[SensitiveParameter] string $accessToken): ResourceOwnerInterface
    {
        $accessTokenObj = new AccessToken(['access_token' => $accessToken]);

        try {
            if ($accessTokenObj->hasExpired()) {
                //try to refresh it
                try {
                    $newAccessToken = $this->getProvider($this->options)->getAccessToken('refresh_token', [
                        'refresh_token' => $accessTokenObj->getRefreshToken(),
                    ]);
                    $accessTokenObj = $newAccessToken;
                } catch (IdentityProviderException) {
                    throw new ExpiredTokenException('OAuth AccessToken has expired and couldnt be refreshed');
                }
            }
        } catch (RuntimeException) {
            //À voir :un moyen de vérifier ça...quel impact sur l'appel suivant si c'est expiré?
        }

        return $this->getProvider($this->options)->getResourceOwner($accessTokenObj);
    }
}
