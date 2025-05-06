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
use App\ApiResource\Amenagement;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

readonly class AmenagementDenormalizer implements DenormalizerInterface
{

    public function __construct(
        #[Autowire(service: 'api_platform.jsonld.normalizer.item')]
        private AbstractItemNormalizer $itemNormalizer,
    )
    {
    }

    #[Override] public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        /**
         * @var Amenagement $amenagement
         */
        $amenagement = $this->itemNormalizer->denormalize($data, $type, $format, $context);
        $amenagement->uid = $context['uri_variables']['uid'];

        return $amenagement;
    }

    #[Override] public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === Amenagement::class;
    }

    #[Override] public function getSupportedTypes(?string $format): array
    {
        return [
            Amenagement::class => true,
        ];
    }
}