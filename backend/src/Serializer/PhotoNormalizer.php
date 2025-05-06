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

use App\ApiResource\Photo;
use ArrayObject;
use Override;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PhotoNormalizer implements NormalizerInterface
{

    /**
     * @param Photo $data
     * @param string|null $format
     * @param array $context
     * @return array|string|int|float|bool|ArrayObject|null
     */
    #[Override] public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
    {
        return [$data->data];
    }

    #[Override] public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!$data instanceof Photo || !in_array($format, ['jpeg'])) {
            return false;
        }

        return true;
    }

    #[Override] public function getSupportedTypes(?string $format): array
    {
        if (!in_array($format, ['jpeg'])) {
            return [];
        }

        return [Photo::class => false];
    }
}