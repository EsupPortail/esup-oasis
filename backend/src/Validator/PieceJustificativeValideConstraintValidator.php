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

use App\ApiResource\AvisEse;
use App\ApiResource\Entretien;
use App\ApiResource\PieceJointeBeneficiaire;
use App\ApiResource\Reponse;
use App\ApiResource\Telechargement;
use App\ApiResource\ValeurParametre;
use App\Entity\Fichier;
use App\Repository\FichierRepository;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PieceJustificativeValideConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly FichierRepository $fichierRepository,
        private readonly Security $security,
    ) {}

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PieceJustificativeValideConstraint) {
            throw new UnexpectedTypeException($constraint, PieceJustificativeValideConstraint::class);
        }

        if (null === $value || '' === $value || [] === $value) {
            return;
        }

        $items = is_iterable($value) ? $value : [$value];
        $parent = $this->context->getObject();
        $currentUser = $this->security->getUser();

        foreach ($items as $item) {
            if (!$item instanceof Telechargement) {
                throw new UnexpectedValueException($item, Telechargement::class);
            }

            $fichier = null !== $item->id ? $this->fichierRepository->find($item->id) : null;
            if (null === $fichier) {
                $this->context->buildViolation($constraint->message)->addViolation();
                continue;
            }

            if ($this->estDejaRattacheAuParent($fichier, $parent)) {
                continue;
            }

            if ($this->estDejaUtiliseAilleurs($fichier)) {
                $this->context->buildViolation($constraint->messageDejaUtilise)->addViolation();
                continue;
            }

            if (
                null === $currentUser
                || $fichier->getProprietaire()?->getUserIdentifier() !== $currentUser->getUserIdentifier()
            ) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
        }
    }

    private function estDejaRattacheAuParent(Fichier $fichier, ?object $parent): bool
    {
        return match (true) {
            $parent instanceof Reponse => $fichier->getReponses()->exists(
                fn(int $k, \App\Entity\Reponse $r) => (
                    $r->getRepondant()?->getUid() === $parent->repondant->uid
                    && $r->getCampagne()?->getId() === $parent->demande->campagne->id
                    && $r->getQuestion()?->getId() === $parent->questionId
                ),
            ),
            $parent instanceof AvisEse => null !== $parent->id && $fichier->getAvisEse()?->getId() === $parent->id,
            $parent instanceof Entretien => null !== $parent->id && $fichier->getEntretien()?->getId() === $parent->id,
            $parent instanceof PieceJointeBeneficiaire => (
                null !== $parent->id && $fichier->getPieceJointeBeneficiaire()?->getId() === $parent->id
            ),
            $parent instanceof ValeurParametre => (
                null !== $parent->id
                && $fichier->getValeurParametres()->exists(
                    fn(int $k, \App\Entity\ValeurParametre $vp) => $vp->getId() === $parent->id,
                )
            ),
            default => false,
        };
    }

    private function estDejaUtiliseAilleurs(Fichier $fichier): bool
    {
        return (
            null !== $fichier->getAvisEse()
            || null !== $fichier->getEntretien()
            || null !== $fichier->getPieceJointeBeneficiaire()
            || null !== $fichier->getDecisionAmenagementExamens()
            || null !== $fichier->getBilan()
            || !$fichier->getValeurParametres()->isEmpty()
            || !$fichier->getReponses()->isEmpty()
        );
    }
}
