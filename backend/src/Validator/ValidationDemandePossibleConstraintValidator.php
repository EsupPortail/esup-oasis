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

use App\ApiResource\Demande;
use App\ApiResource\QuestionDemande;
use App\ApiResource\Reponse;
use App\Entity\Question;
use App\Repository\OptionReponseRepository;
use Exception;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidationDemandePossibleConstraintValidator extends ConstraintValidator
{

    public function __construct(private readonly OptionReponseRepository $optionReponseRepository)
    {

    }

    /**
     * @param mixed $value
     * @param ValidationDemandePossibleConstraint $constraint
     * @return void
     */
    #[Override] public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidationDemandePossibleConstraint) {
            throw new UnexpectedTypeException($constraint, ValidationDemandePossibleConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Reponse) {
            throw new UnexpectedValueException($value, Reponse::class);
        }

        if ($value->question->typeReponse !== Question::TYPE_SUBMIT) {
            return;
        }
        if ($value->demande->complete) {
            return;
        }

        foreach ($value->demande->etapes as $etape) {
            foreach ($etape->questions as $question) {
                try {
                    $this->validerQuestion($value->demande, $question, $constraint);
                } catch (Exception) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ question }}', $question->libelle)
                        ->addViolation();
                }
            }
        }
    }

    /**
     * @param Demande $demande
     * @param QuestionDemande $question
     * @param ValidationDemandePossibleConstraint $constraint
     * @return void
     * @throws Exception
     */
    private function validerQuestion(Demande                             $demande,
                                     QuestionDemande                     $question,
                                     ValidationDemandePossibleConstraint $constraint): void
    {
        if ($question->obligatoire) {
            //obligatoire...on a une réponse?
            $reponseManquante = match ($question->typeReponse) {
                Question::TYPE_TEXT, Question::TYPE_TEXTAREA => trim($question->reponse?->commentaire ?? '') == '',
                Question::TYPE_SELECT, Question::TYPE_CHECKBOX => count($question->reponse?->optionsReponses ?? []) === 0,
                default => false
            };
            if ($reponseManquante) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ question }}', $question->libelle)
                    ->addViolation();
            }
        }
        if (!in_array($question->typeReponse, [Question::TYPE_CHECKBOX, Question::TYPE_SELECT])) {
            //pas de questions liées possibles
            return;
        }
        if (null !== $question->tableOptions) {
            //pas de questions liées aux tables de ref, trop compliqué
            return;
        }
        foreach ($question->reponse?->optionsReponses ?? [] as $optionReponse) {
            //on boucle sur les questions liées à la réponse donnée
            $option = $this->optionReponseRepository->find($optionReponse->id);
            foreach ($option?->getQuestionsLiees() as $questionsLiee) {
                //on va chercher la QuestionDemande dans la Demande
                try {
                    $this->validerQuestion($demande, $demande->getQuestionDemande($questionsLiee), $constraint);
                } catch (Exception) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ question }}', $questionsLiee->getLibelle())
                        ->addViolation();
                }
            }
        }

    }

}