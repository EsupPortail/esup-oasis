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

namespace App\Controller;

use App\Service\ErreurLdapException;
use App\Service\OAuthService;
use App\State\Utilisateur\UtilisateurManager;
use DateTime;
use JsonException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnexpectedValueException;

#[Route(path: '/connect/oauth', name: 'connect_oauth_')]
class OAuthController extends AbstractController
{
    public function __construct(
        private readonly OAuthService $oauthService,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
    ) {}

    /**
     * Démarre l'auth avec Oauth et retourne un accessToken (i.e. reproduit pour tests le boulot attendu coté front,
     * l'accestoken étant ensuite passé à l'api pour obtenir un jwt api)
     */
    #[Route(path: '/accessToken', name: 'accesstoken')]
    public function getAccessToken(
        Request $request,
        #[Autowire('%env(JWT_COOKIE_DOMAIN)%')]
        string $cookieDomain,
    ): Response {
        $cookieDomainNorm = empty($cookieDomain) ? null : $cookieDomain;
        if ($cookieDomainNorm !== null) {
            $host = $request->getHost();
            $cleanDomain = ltrim($cookieDomainNorm, '.');
            if ($host !== $cleanDomain && !str_ends_with($host, '.' . $cleanDomain)) {
                $cookieDomainNorm = null;
            }
        }
        try {
            $token = $this->oauthService->getAccessToken(
                $request,
                $this->generateUrl('connect_oauth_accesstoken', [], UrlGeneratorInterface::ABSOLUTE_URL),
                $cookieDomainNorm,
            );
            if ($token instanceof Response) {
                return $token;
            }
            $response = new JsonResponse(['token' => $token]);
            $response->headers->clearCookie(
                'oauth_state',
                '/',
                $cookieDomainNorm,
                $request->isSecure(),
                true,
                Cookie::SAMESITE_LAX,
            );
            $response->headers->clearCookie(
                'oauth_code_verifier',
                '/',
                $cookieDomainNorm,
                $request->isSecure(),
                true,
                Cookie::SAMESITE_LAX,
            );
            return $response;
        } catch (IdentityProviderException|UnexpectedValueException $exception) {
            $response = new Response($exception->getMessage(), 500);
            $response->headers->clearCookie(
                'oauth_state',
                '/',
                $cookieDomainNorm,
                $request->isSecure(),
                true,
                Cookie::SAMESITE_LAX,
            );
            $response->headers->clearCookie(
                'oauth_code_verifier',
                '/',
                $cookieDomainNorm,
                $request->isSecure(),
                true,
                Cookie::SAMESITE_LAX,
            );
            return $response;
        }
    }

    /**
     * Réalise une auth complète telle que le ferait une appli monobloc et retourne tous les attributé récupérés via
     * le serveur d'auth + un JWT valide pour l'api
     */
    #[Route(path: '/', name: 'login')]
    public function fullAuthentication(
        Request $request,
        UtilisateurManager $utilisateurManager,
        #[Autowire('%env(JWT_TOKEN_TTL)%')]
        int $ttl,
        #[Autowire('%env(JWT_COOKIE_NAME)%')]
        string $cookieName,
        #[Autowire('%env(JWT_COOKIE_DOMAIN)%')]
        string $cookieDomain,
    ): Response {
        /**
         * https://oauth2-client.thephpleague.com/usage/
         * https://apereo.github.io/cas/6.0.x/installation/OAuth-OpenId-Authentication.html#authorization-code
         */
        $cookieDomainNorm = empty($cookieDomain) ? null : $cookieDomain;
        if ($cookieDomainNorm !== null) {
            $host = $request->getHost();
            $cleanDomain = ltrim($cookieDomainNorm, '.');
            if ($host !== $cleanDomain && !str_ends_with($host, '.' . $cleanDomain)) {
                $cookieDomainNorm = null;
            }
        }
        try {
            $token = $this->oauthService->getAccessToken(
                $request,
                $this->generateUrl('connect_oauth_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
                $cookieDomainNorm,
            );

            if ($token instanceof Response) {
                return $token;
            }

            $resourceOwner = $this->oauthService->getResourceOwnerFromToken($token);
            $uid = $this->oauthService->extractResourceOwnerUid($resourceOwner);
            $user = $utilisateurManager->parUid($uid, true);
            $infos = $resourceOwner->toArray();
            $infos['id'] = $uid;

            $infos['tokenApi'] = $this->jwtTokenManager->create($user);

            $jsonResponse = new JsonResponse($infos);

            $jsonResponse->headers->setCookie(Cookie::create(
                $cookieName,
                $infos['tokenApi'],
                new DateTime()->modify(sprintf('+ %s seconds', $ttl)),
                '/',
                $cookieDomainNorm,
                true,
                true,
                false,
                Cookie::SAMESITE_STRICT,
            ));

            $jsonResponse->headers->clearCookie(
                'oauth_state',
                '/',
                $cookieDomainNorm,
                $request->isSecure(),
                true,
                Cookie::SAMESITE_LAX,
            );
            $jsonResponse->headers->clearCookie(
                'oauth_code_verifier',
                '/',
                $cookieDomainNorm,
                $request->isSecure(),
                true,
                Cookie::SAMESITE_LAX,
            );

            return $jsonResponse;
        } catch (IdentityProviderException|UnexpectedValueException $exception) {
            $response = new Response($exception->getMessage(), 500);
            $response->headers->clearCookie(
                'oauth_state',
                '/',
                $cookieDomainNorm,
                $request->isSecure(),
                true,
                Cookie::SAMESITE_LAX,
            );
            $response->headers->clearCookie(
                'oauth_code_verifier',
                '/',
                $cookieDomainNorm,
                $request->isSecure(),
                true,
                Cookie::SAMESITE_LAX,
            );
            return $response;
        }
    }

    /**
     * Point d'entrée "normal" pour le front : prend en entrée un accessToken OAuth ou un code (PKCE) et retourne un JWT api
     *
     * @param Request $request
     * @param UtilisateurManager $utilisateurManager
     * @param int $ttl
     * @param string $cookieName
     * @param string $cookieDomain
     * @return Response
     * @throws ErreurLdapException
     */
    #[Route(path: '/token', name: 'user_token')]
    public function getApiJwtFromCasAccessToken(
        Request $request,
        UtilisateurManager $utilisateurManager,
        #[Autowire('%env(JWT_TOKEN_TTL)%')]
        int $ttl,
        #[Autowire('%env(JWT_COOKIE_NAME)%')]
        string $cookieName,
        #[Autowire('%env(JWT_COOKIE_DOMAIN)%')]
        string $cookieDomain,
    ): Response {
        $isJson = null !== $request->query->get('json');
        if (!$isJson) {
            $accessToken = $request->attributes->get('accessToken');
            if (null === $accessToken) {
                return new JsonResponse(['error' => 'parametre accessToken manquant'], 400);
            }
        } else {
            //todo: utiliser json_validate
            try {
                $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return new JsonResponse(['error' => 'json mal formé en entrée'], 400);
            }

            if (isset($payload['code'])) {
                try {
                    $codeVerifier = $payload['codeVerifier'] ?? $payload['code_verifier'] ?? null;
                    $redirectUri = $payload['redirectUri'] ?? $payload['redirect_uri'] ?? null;
                    $accessToken = $this->oauthService->exchangeCodeForToken(
                        $payload['code'],
                        $codeVerifier,
                        $redirectUri,
                    );
                } catch (IdentityProviderException|UnexpectedValueException $e) {
                    return new JsonResponse(['error' => $e->getMessage()], 400);
                }
            } elseif (isset($payload['accessToken'])) {
                $accessToken = $payload['accessToken'];
            } else {
                return new JsonResponse(['error' => 'parametre code ou accessToken manquant'], 400);
            }
        }

        try {
            $resourceOwner = $this->oauthService->getResourceOwnerFromToken($accessToken);
            $uid = $this->oauthService->extractResourceOwnerUid($resourceOwner);
            $user = $utilisateurManager->parUid($uid, true);
        } catch (ExpiredTokenException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        } catch (UnexpectedValueException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        $token = $this->jwtTokenManager->create($user);
        $jsonResponse = new JsonResponse(['token' => $token]);

        $jsonResponse->headers->setCookie(Cookie::create(
            $cookieName,
            $token,
            new DateTime()->modify(sprintf('+ %s seconds', $ttl)),
            '/',
            $cookieDomain,
            true,
            true,
            false,
            Cookie::SAMESITE_STRICT,
        ));
        return $jsonResponse;
    }
}
