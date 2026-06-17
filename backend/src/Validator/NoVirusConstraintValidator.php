<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Validator;

use App\ApiResource\Telechargement;
use App\Service\AntivirusService;
use Override;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NoVirusConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AntivirusService $antivirusService,
    ) {}

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoVirusConstraint) {
            throw new UnexpectedTypeException($constraint, NoVirusConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Telechargement) {
            throw new UnexpectedValueException($value, Telechargement::class);
        }

        try {
            if (!$this->antivirusService->ping()) {
                if ($this->antivirusService->strictMode) {
                    $this->context
                        ->buildViolation('Antivirus indisponible, veuillez réessayer plus tard')
                        ->addViolation();
                }
                return;
            }
        } catch (RuntimeException) {
            if ($this->antivirusService->strictMode) {
                $this->context->buildViolation('Antivirus indisponible, veuillez réessayer plus tard')->addViolation();
            }
            return;
        }

        if (!$this->antivirusService->scan($value->file->getPathname())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
