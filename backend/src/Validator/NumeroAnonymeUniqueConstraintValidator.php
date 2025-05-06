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

use App\ApiResource\Utilisateur;
use App\Repository\UtilisateurRepository;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NumeroAnonymeUniqueConstraintValidator extends ConstraintValidator
{

    public function __construct(private readonly UtilisateurRepository $utilisateurRepository)
    {
    }


    #[Override] public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NumeroAnonymeUniqueConstraint) {
            throw new UnexpectedTypeException($constraint, NumeroAnonymeUniqueConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Utilisateur) {
            throw new UnexpectedValueException($value, Utilisateur::class);
        }

        if ($value->numeroAnonyme === null) {
            return;
        }

        //on regarde si le numéro est déjà présent en base pour un autre utilisateur
        $existants = array_filter(
            $this->utilisateurRepository->findBy(
                [
                    'numeroAnonyme' => $value->numeroAnonyme,
                ]
            ),
            fn(\App\Entity\Utilisateur $user) => $user->getUid() !== $value->uid
        );

        if (!empty($existants)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }

    }
}