<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Message;

/**
 * Décision à déposer dans le parapheur : envoyé par SignatureElectronique::demanderSignature, une fois la
 * décision passée en signature.
 */
class SignatureDecisionDemandeeMessage
{
    public function __construct(
        protected int $idDecision,
        protected string $uidDemandeur,
    ) {}

    public function getIdDecision(): int
    {
        return $this->idDecision;
    }

    public function getUidDemandeur(): string
    {
        return $this->uidDemandeur;
    }
}
