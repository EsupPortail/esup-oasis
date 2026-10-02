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

class DateAvisMedecinRequiseConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DateAvisMedecinRequiseConstraint) {
            throw new UnexpectedTypeException($constraint, DateAvisMedecinRequiseConstraint::class);
        }

        if (!$value instanceof DecisionResource) {
            return;
        }

        // évaluée par le provider (cf. ExigenceAvisMedical), en lecture seule pour le client
        if (!$value->dateAvisMedecinRequise) {
            return;
        }

        // la demande du gestionnaire comme l'envoi par l'administrateur
        if (!in_array($value->etat, [
            DecisionAmenagementExamens::ETAT_VALIDE,
            DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE,
        ], true)) {
            return;
        }

        if (null !== $value->dateAvisMedecin) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath('dateAvisMedecin')
            ->addViolation();
    }
}
