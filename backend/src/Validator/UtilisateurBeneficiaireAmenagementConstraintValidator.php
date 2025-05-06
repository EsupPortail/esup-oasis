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

use App\ApiResource\Amenagement;
use App\ApiResource\Utilisateur;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use App\Util\AnneeUniversitaireAwareTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UtilisateurBeneficiaireAmenagementConstraintValidator extends ConstraintValidator
{
    use UtilisateurBeneficiaireValidatorTrait;
    use AnneeUniversitaireAwareTrait;

    public function __construct(private readonly UtilisateurManager $utilisateurManager,
                                private readonly TransformerService $transformerService)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UtilisateurBeneficiaireAmenagementConstraint) {
            throw new UnexpectedTypeException($constraint, UtilisateurBeneficiaireAmenagementConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Amenagement) {
            throw new UnexpectedValueException($value, Amenagement::class);
        }

        //pour chaque beneficiaire, est-ce qu'on a un profil valide pour la date de l'événement?
        $dateObservee = match ($value->debut) {
            null => match ($value->semestre1) {
                true => $this->getDebutSemestre1(),
                false => $this->getDebutSemestre2(),
            },
            default => $value->debut
        };
        $utilisateur = $this->transformerService->transform($this->utilisateurManager->parUid($value->uid), Utilisateur::class);

        if (!$this->utilisateurValide($utilisateur, $dateObservee)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $utilisateur->uid)
                ->addViolation();
        }


    }
}