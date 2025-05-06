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

use App\ApiResource\Evenement;
use App\ApiResource\InterventionForfait;
use App\Repository\PeriodeRHRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PeriodeNonBloqueeConstraintValidator extends ConstraintValidator
{
    use ClockAwareTrait;

    public function __construct(private readonly PeriodeRHRepository $periodeRHRepository,
                                private readonly Security            $security)
    {

    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PeriodeNonBloqueeConstraint) {
            throw new UnexpectedTypeException($constraint, PeriodeNonBloqueeConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        //les admins peuvent passer outre
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($value instanceof Evenement) {
            $this->checkEvenement($value, $constraint);
            return;
        }
        if ($value instanceof InterventionForfait) {
            $this->checkInterventionForfait($value, $constraint);
            return;
        }

        throw new UnexpectedValueException($value, Evenement::class);
    }

    private function checkEvenement(Evenement $value, PeriodeNonBloqueeConstraint $constraint): void
    {
        //la période à considérer est celle incluant la date de début de l'événement
        $periode = $this->periodeRHRepository->findPeriodePourDate($value->debut);
        if (null !== $periode) {
            if ($periode->getButoir()->format('Ymd') < $this->now()->format('Ymd')) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
        } else {
            //on a une période postérieure ?
            if ($this->periodeRHRepository->periodeExisteApres($value->debut)) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
        }
    }

    private function checkInterventionForfait(InterventionForfait $value, PeriodeNonBloqueeConstraint $constraint): void
    {
        if ($value->periode->butoir->format('Ymd') < $this->now()->format('Ymd')) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}