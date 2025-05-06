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

/**
 * https://github.com/symfony/symfony/pull/53214
 *
 */

namespace App\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DoctrineConnectionListener implements EventSubscriberInterface
{
    private int $lastChecked = 0;

    public function __construct(
        #[Autowire(service: 'service_container')] private readonly ContainerInterface $container,
        #[Autowire(env: 'DOCTRINE_CHECK_TIMING')] private readonly int                $checkTiming)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $connection = $this->container->get('database_connection');

        if (!$connection instanceof Connection) {
            throw new RuntimeException(sprintf('Is not an instance of "%s".', Connection::class));
        }

        if (time() - $this->lastChecked >= $this->checkTiming) {
            $connection->close();
            $this->lastChecked = time();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}