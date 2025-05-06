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

namespace App\Serializer;

use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\ApiResource\Demande;
use App\ApiResource\Question;
use App\ApiResource\Reponse;
use App\ApiResource\Utilisateur;
use App\Repository\DemandeRepository;
use App\Repository\QuestionRepository;
use App\State\TransformerService;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

readonly class ReponseDenormalizer implements DenormalizerInterface
{
    public function __construct(private AbstractItemNormalizer $itemNormalizer,
                                private QuestionRepository     $questionRepository,
                                private DemandeRepository      $demandeRepository,
                                private TransformerService     $transformerService)
    {
    }


    #[Override] public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        /**
         * @var Reponse $reponse
         */
        $reponse = $this->itemNormalizer->denormalize($data, $type, $format, $context);
        $reponse->questionId = $context['uri_variables']['questionId'];
        $reponse->demandeId = $context['uri_variables']['demandeId'];

        $demande = $this->demandeRepository->find($reponse->demandeId);
        $question = $this->questionRepository->find($reponse->questionId);

        if (null === $demande || null === $question) {
            throw new NotFoundHttpException("Demande / question inexistante");
        }

        $reponse->demande = $this->transformerService->transform($demande, Demande::class);
        $reponse->question = $this->transformerService->transform($question, Question::class);
        $reponse->repondant = $this->transformerService->transform($demande->getDemandeur(), Utilisateur::class);

        return $reponse;
    }

    #[Override] public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === Reponse::class;
    }

    #[Override] public function getSupportedTypes(?string $format): array
    {
        return [
            Reponse::class => true,
        ];
    }
}