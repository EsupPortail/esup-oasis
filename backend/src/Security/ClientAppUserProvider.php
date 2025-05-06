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

namespace App\Security;

use App\Entity\ApplicationCliente;
use App\Repository\ApplicationClienteRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

readonly class ClientAppUserProvider implements UserProviderInterface
{

    public function __construct(private ApplicationClienteRepository $applicationClienteRepository,
                                private LoggerInterface              $logger)
    {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return ApplicationCliente::class == $class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $realIdentifier = substr($identifier, strlen('[APP]'));
        $app = $this->applicationClienteRepository->findOneBy([
            'identifiant' => $realIdentifier,
        ]);

        if (null === $app) {
            throw new UserNotFoundException($identifier);
        }

        return $app;
    }

    /**
     * @param $appId
     * @param $apiKey
     * @return UserInterface
     */
    public function authenticateApp($appId, $apiKey): UserInterface
    {
        $app = $this->applicationClienteRepository->findOneBy([
            'identifiant' => $appId,
        ]);
        if (null == $app) {
            $this->logger->error("Tentative de connection pour app " . $appId);
            throw new AccessDeniedException('App inconnue');
        }
        if ($app->getApiKey() !== $apiKey) {
            $this->logger->error("ApiKey incorrecte pour app " . $appId);
            throw new AccessDeniedException('ApiKey incorrecte');
        }

        return $app;
    }
}