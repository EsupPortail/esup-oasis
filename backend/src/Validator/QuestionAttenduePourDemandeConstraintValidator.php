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

use App\ApiResource\Reponse;
use App\Entity\Question;
use App\Repository\DemandeRepository;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class QuestionAttenduePourDemandeConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DemandeRepository $demandeRepository,
    ) {}

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof QuestionAttenduePourDemandeConstraint) {
            throw new UnexpectedTypeException($constraint, QuestionAttenduePourDemandeConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Reponse) {
            throw new UnexpectedValueException($value, Reponse::class);
        }

        $demandeId = $value->demandeId ?? null;
        $questionId = $value->questionId ?? null;

        if (null === $demandeId || null === $questionId) {
            return;
        }

        $demandeEntity = $this->demandeRepository->find($demandeId);
        if (null === $demandeEntity) {
            return;
        }

        $typeDemande = $demandeEntity->getCampagne()->getTypeDemande();
        $questionsAutorisees = [
            Question::QUESTION_DEMANDE_ACCOMPAGNEMENT => true,
        ];

        foreach ($typeDemande->getEtapes() as $etape) {
            foreach ($etape->getQuestionsEtape() as $qe) {
                if (null !== $qe->getQuestion()) {
                    $this->collectQuestionIds($qe->getQuestion(), $questionsAutorisees);
                }
            }
        }

        if (!isset($questionsAutorisees[$questionId])) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    private function collectQuestionIds(Question $question, array &$visited): void
    {
        if (isset($visited[$question->getId()])) {
            return;
        }
        $visited[$question->getId()] = true;
        foreach ($question->getOptionsReponse() as $option) {
            foreach ($option->getQuestionsLiees() as $liee) {
                $this->collectQuestionIds($liee, $visited);
            }
        }
    }
}
