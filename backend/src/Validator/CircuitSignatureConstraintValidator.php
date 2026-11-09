<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Validator;

use App\ApiResource\DecisionAmenagementExamens as DecisionResource;
use App\Entity\DecisionAmenagementExamens;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CircuitSignatureConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CircuitSignatureConstraint) {
            throw new UnexpectedTypeException($constraint, CircuitSignatureConstraint::class);
        }

        if (!$value instanceof DecisionResource) {
            return;
        }

        // la demande du gestionnaire comme l'envoi par l'administrateur
        if (!in_array($value->etat, [
            DecisionAmenagementExamens::ETAT_VALIDE,
            DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE,
        ], true)) {
            return;
        }

        // évalué par DecisionAmenagementManager::versRessource, en lecture seule pour le client
        if (null === $value->motifSignatureImpossible) {
            return;
        }

        $this->context->buildViolation($value->motifSignatureImpossible)
            ->atPath('etat')
            ->addViolation();
    }
}
