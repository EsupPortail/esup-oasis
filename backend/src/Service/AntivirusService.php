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

use App\Message\ErreurTechniqueMessage;
use Exception;
use Niisan\ClamAV\Scanner;
use Niisan\ClamAV\ScannerFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ResetInterface;

class AntivirusService implements ResetInterface
{
    protected Scanner $clamav;
    private bool $online = true;

    public function __construct(
        #[Autowire('%env(CLAMAV_SERVER)%')]
        readonly string $server,
        #[Autowire('%env(CLAMAV_PORT)%')]
        readonly int $port,
        private readonly MessageBusInterface $messageBus,
        #[Autowire('%env(bool:CLAMAV_STRICT_MODE)%')]
        public readonly bool $strictMode,
    ) {
        try {
            $this->clamav = ScannerFactory::create([
                'driver' => 'remote',
                'url' => $server,
                'port' => $this->port,
            ]);
        } catch (Exception $e) {
            $this->messageBus->dispatch(new ErreurTechniqueMessage($e, 'Antivirus indisponible'));
            $this->online = false;
        }
    }

    /**
     * @param string $file
     * @return bool
     */
    public function scan(string $file): bool
    {
        try {
            return $this->online && $this->clamav->scan($file);
        } catch (Exception $e) {
            $this->messageBus->dispatch(new ErreurTechniqueMessage($e, 'Antivirus indisponible'));
            $this->online = false;
            return !$this->strictMode;
        }
    }

    public function ping(): bool
    {
        try {
            return $this->online && $this->clamav->ping();
        } catch (Exception $e) {
            $this->messageBus->dispatch(new ErreurTechniqueMessage($e, 'Antivirus indisponible'));
            return false;
        }
    }

    public function reset(): void
    {
        $this->online = true;
    }
}
