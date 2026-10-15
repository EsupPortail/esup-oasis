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
 * Refuse l'ajout, la modification et la suppression d'un aménagement repris dans la décision ou d'un avis de
 * santé, tant que la décision du bénéficiaire est en signature ou refusée. Le motif vient de
 * DecisionAmenagementManager.
 */
#[Attribute]
class DecisionNonVerrouilleeConstraint extends Constraint
{
    public const string GROUPE_SUPPRESSION = 'decisionNonVerrouillee:delete';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
