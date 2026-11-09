<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Avec un parapheur, refuse la demande d'édition et l'envoi d'une décision dont le circuit de signature ne peut
 * pas être déterminé : elle partirait par e-mail, sans signature. Le motif vient de SignatureElectronique.
 */
#[Attribute]
class CircuitSignatureConstraint extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
