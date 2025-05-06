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
use App\ApiResource\Utilisateur;
use App\State\TransformerService;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;


readonly class DemandeDenormalizer implements DenormalizerInterface
{

    public function __construct(private AbstractItemNormalizer $itemNormalizer,
                                private Security               $security,
                                private TransformerService     $transformerService)
    {
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @return mixed
     * @throws ExceptionInterface
     */
    #[Override] public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        /**
         * @var Demande $demande
         */
        $demande = $this->itemNormalizer->denormalize($data, $type, $format, $context);

        if (null == $demande->demandeur) {
            $demande->demandeur = $this->transformerService->transform($this->security->getUser(), Utilisateur::class);
        }

        return $demande;
    }

    #[Override] public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === Demande::class;
    }

    #[Override] public function getSupportedTypes(?string $format): array
    {
        return [
            Demande::class => true,
        ];
    }

}