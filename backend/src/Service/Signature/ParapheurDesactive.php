<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\Signature;

use LogicException;

/**
 * Parapheur par défaut : aucun, les décisions sont envoyées par e-mail.
 */
class ParapheurDesactive extends AbstractParapheur
{
    public const string ID = 'aucun';

    public function getProviderId(): string
    {
        return self::ID;
    }

    public function estDisponible(): bool
    {
        return false;
    }

    public function deposer(string $pdf, string $circuit, string $libelle, ?string $destinataire): string
    {
        throw new LogicException('Aucun parapheur n\'est configuré.');
    }

    public function suivre(string $documentId): SuiviSignature
    {
        throw new LogicException('Aucun parapheur n\'est configuré.');
    }

    public function telecharger(string $documentId): string
    {
        throw new LogicException('Aucun parapheur n\'est configuré.');
    }
}
