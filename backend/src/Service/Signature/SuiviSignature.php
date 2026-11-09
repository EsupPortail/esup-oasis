<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\Signature;

use DateTimeImmutable;

/**
 * État d'un document dans le parapheur : une constante ETAT_SIGNATURE_* de la décision, et la date de
 * la dernière signature une fois le document signé.
 */
final readonly class SuiviSignature
{
    public function __construct(
        public string $etat,
        public ?DateTimeImmutable $dateSignature = null,
    ) {}
}
