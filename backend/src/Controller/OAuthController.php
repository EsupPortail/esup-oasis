<?php

/*
 * Copyright (c) 2024. Esup - Université de Bordeaux.
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
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnexpectedValueException;

#[Route(path: '/connect/oauth', name: 'connect_oauth_')]
class OAuthController extends AbstractController
{
    public function __construct(private readonly OAuthService             $oauthService,
                                private readonly JWTTokenManagerInterface $jwtTokenManager)
    {
    }

    /**
     * Démarre l'auth avec Oauth et retourne un accessToken (i.e. reproduit pour tests le boulot attendu coté front,
     * l'accestoken étant ensuite passé à l'api pour obtenir un jwt api)
     */
    #[Route(path: '/accessToken', name: 'accesstoken')]
    public function getAccessToken(): Response
    {
        try {
            $token = $this->oauthService->getAccessToken(
                $this->generateUrl("connect_oauth_accesstoken", [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
            return new JsonResponse(['token' => $token]);
        } catch (IdentityProviderException|UnexpectedValueException $exception) {
            return new Response($exception->getMessage(), 500);
        }
    }

    /**
     * Réalise une auth complète telle que le ferait une appli monobloc et retourne tous les attributs récupérés via
     * le serveur d'auth + un JWT valide pour l'api
     */
    #[Route(path: '/', name: 'login')]
    public function fullAuthentication(UtilisateurManager                             $utilisateurManager,
                                       #[Autowire('%env(JWT_TOKEN_TTL)%')] int        $ttl,
                                       #[Autowire('%env(JWT_COOKIE_NAME)%')] string   $cookieName,
                                       #[Autowire('%env(JWT_COOKIE_DOMAIN)%')] string $cookieDomain): Response
    {
        /**
         * https://oauth2-client.thephpleague.com/usage/
         * https://apereo.github.io/cas/6.0.x/installation/OAuth-OpenId-Authentication.html#authorization-code
         */
        try {
            $token = $this->oauthService->getAccessToken(
                $this->generateUrl("connect_oauth_login", [], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            $resourceOwner = $this->oauthService->getResourceOwnerFromToken($token);
            $uid = $resourceOwner->getId();
            $user = $utilisateurManager->parUid($uid, true);
            $infos = $resourceOwner->toArray();

            $infos['tokenApi'] = $this->jwtTokenManager->create($user);

            $jsonResponse = new JsonResponse($infos);

            $jsonResponse->headers->setCookie(
                Cookie::create(
                    $cookieName,
                    $infos['tokenApi'],
                    (new DateTime())->modify(sprintf('+ %s seconds', $ttl)),
                    "/",
                    $cookieDomain,
                    true,
                    true,
                    false,
                    Cookie::SAMESITE_STRICT)
            );

            return $jsonResponse;
        } catch (IdentityProviderException|UnexpectedValueException $exception) {
            return new Response($exception->getMessage(), 500);
        }
    }

    /**
     * Point d'entrée "normal" pour le front : prend en entrée un accessToken OAuth et retourne un JWT api
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
    public function getApiJwtFromCasAccessToken(Request                                        $request,
                                                UtilisateurManager                             $utilisateurManager,
                                                #[Autowire('%env(JWT_TOKEN_TTL)%')] int        $ttl,
                                                #[Autowire('%env(JWT_COOKIE_NAME)%')] string   $cookieName,
                                                #[Autowire('%env(JWT_COOKIE_DOMAIN)%')] string $cookieDomain): Response
    {
        $isJson = (null !== $request->get('json'));
        if (!$isJson) {
            $accessToken = $request->get('accessToken');
            if (null === $accessToken) {
                return new JsonResponse(['error' => 'parametre accessToken manquant'], 400);
            }
        } else {
            //todo: utiliser json_validate
            try {
                $accessToken = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)['accessToken'];
            } catch (JsonException) {
                return new JsonResponse(['error' => 'json mal formé en entrée'], 400);
            }
        }
        $resourceOwner = $this->oauthService->getResourceOwnerFromToken($accessToken);
        $user = $utilisateurManager->parUid($resourceOwner->getId(), true);

        $token = $this->jwtTokenManager->create($user);
        $jsonResponse = new JsonResponse(['token' => $token]);

        $jsonResponse->headers->setCookie(
            Cookie::create(
                $cookieName,
                $token,
                (new DateTime())->modify(sprintf('+ %s seconds', $ttl)),
                "/",
                $cookieDomain,
                true,
                true,
                false,
                Cookie::SAMESITE_STRICT)
        );
        return $jsonResponse;
    }
}