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

namespace App\Validator;

use App\ApiResource\BeneficiaireProfil;
use App\Repository\EvenementRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class BeneficiaireSupprimableConstraintValidator extends ConstraintValidator
{

    public function __construct(private readonly EvenementRepository $evenementRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BeneficiaireSupprimableConstraint) {
            throw new UnexpectedTypeException($constraint, BeneficiaireSupprimableConstraint::class);
        }

        if (!$value instanceof BeneficiaireProfil) {
            throw new UnexpectedValueException($value, BeneficiaireProfil::class);
        }

        if ($this->evenementRepository->countByIdBeneficiaire($value->id) > 0) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}