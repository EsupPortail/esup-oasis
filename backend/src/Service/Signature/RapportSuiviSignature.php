<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

namespace App\Service\Signature;

/** Bilan d'un passage du suivi de signature. */
readonly class RapportSuiviSignature
{
    public function __construct(
        public int $examinees = 0,
        public int $signees = 0,
        public int $refusees = 0,
        public int $closes = 0,
        public int $erreurs = 0,
    ) {}

    public function avec(
        int $examinees = 0,
        int $signees = 0,
        int $refusees = 0,
        int $closes = 0,
        int $erreurs = 0,
    ): self {
        return new self(
            examinees: $this->examinees + $examinees,
            signees: $this->signees + $signees,
            refusees: $this->refusees + $refusees,
            closes: $this->closes + $closes,
            erreurs: $this->erreurs + $erreurs,
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'examinees' => $this->examinees,
            'signees' => $this->signees,
            'refusees' => $this->refusees,
            'closes' => $this->closes,
            'erreurs' => $this->erreurs,
        ];
    }
}
