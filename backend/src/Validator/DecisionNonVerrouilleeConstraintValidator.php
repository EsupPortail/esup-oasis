<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Validator;

use App\ApiResource\Amenagement;
use App\ApiResource\AvisEse;
use App\Repository\AmenagementRepository;
use App\Repository\UtilisateurRepository;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DecisionNonVerrouilleeConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly RequestStack $request,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly DecisionAmenagementManager $decisionAmenagementManager,
        private readonly AmenagementRepository $amenagementRepository,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DecisionNonVerrouilleeConstraint) {
            throw new UnexpectedTypeException($constraint, DecisionNonVerrouilleeConstraint::class);
        }

        if (!$value instanceof Amenagement && !$value instanceof AvisEse) {
            throw new UnexpectedValueException($value, Amenagement::class . '|' . AvisEse::class);
        }

        // seuls les aménagements repris dans la décision la feraient mentir, y compris celui dont on change le type
        if ($value instanceof Amenagement && !$this->reprisDansLaDecision($value)) {
            return;
        }

        // le bénéficiaire désigné par l'URI : l'avis créé n'en a pas encore, l'aménagement peut en avoir plusieurs
        $uid = $this->request->getCurrentRequest()?->attributes->get('uid');
        $beneficiaire = null === $uid ? null : $this->utilisateurRepository->findOneBy(['uid' => $uid]);
        if (null === $beneficiaire) {
            return;
        }

        $motif = $this->decisionAmenagementManager->motifVerrou($beneficiaire);
        if (null !== $motif) {
            $this->context->buildViolation($motif)->addViolation();
        }
    }

    private function reprisDansLaDecision(Amenagement $amenagement): bool
    {
        // ni type dans la requête ni entité : la requête est incomplète, NotNull la refuse
        if (!new ReflectionProperty(Amenagement::class, 'typeAmenagement')->isInitialized($amenagement)
            && null === $amenagement->id) {
            return false;
        }

        if (true === $amenagement->typeAmenagement->decision) {
            return true;
        }

        // type avant la modification : l'entité n'est mise à jour qu'au traitement
        $avant = null === $amenagement->id ? null : $this->amenagementRepository->find($amenagement->id);

        return true === $avant?->getType()?->isDecision();
    }
}
