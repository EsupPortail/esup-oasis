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

namespace App\Service;

use App\Message\ErreurTechniqueMessage;
use Exception;
use Niisan\ClamAV\Scanner;
use Niisan\ClamAV\ScannerFactory;
use Symfony\Component\Messenger\MessageBusInterface;

class AntivirusService
{
    protected Scanner $clamav;
    private bool $online = true;

    public function __construct(readonly string                      $server,
                                readonly int                         $port,
                                private readonly MessageBusInterface $messageBus)
    {
        try {
            $this->clamav = ScannerFactory::create(
                [
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
            return true;
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

}