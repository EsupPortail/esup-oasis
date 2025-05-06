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

use App\Entity\Utilisateur;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class RoleCommissionValideConstraintValidator extends ConstraintValidator
{

    protected const array VALID_VALUES = [
        Utilisateur::ROLE_ATTRIBUER_PROFIL,
        Utilisateur::ROLE_VALIDER_CONFORMITE_DEMANDE,
    ];

    /**
     * @param mixed $value
     * @param Constraint $constraint
     * @return void
     */
    #[Override] public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RoleCommissionValideConstraint) {
            throw new UnexpectedTypeException($constraint, RoleCommissionValideConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        foreach ($value as $val) {
            if (!in_array($val, self::VALID_VALUES)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ role }}', $val)
                    ->setParameter('{{ valides }}', '[' . implode(', ', self::VALID_VALUES) . ']')
                    ->addViolation();
            }
        }

    }
}