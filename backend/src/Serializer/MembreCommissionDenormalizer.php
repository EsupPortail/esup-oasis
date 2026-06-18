<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

declare(strict_types=1);

namespace App\Serializer;

use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\ApiResource\Commission;
use App\ApiResource\MembreCommission;
use App\ApiResource\Utilisateur;
use App\Repository\CommissionRepository;
use App\Repository\MembreCommissionRepository;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

readonly class MembreCommissionDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private MembreCommissionRepository $membreCommissionRepository,
        private CommissionRepository $commissionRepository,
        private UtilisateurManager $utilisateurManager,
    )
    {
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $commissionId = $context['uri_variables']['commissionId'] ?? null;
        $userUid = $context['uri_variables']['uid'] ?? null;

        $utilisateur = $this->utilisateurManager->parUid($userUid);
        $commission = $this->commissionRepository->find($commissionId);

        $entity = $this->membreCommissionRepository->findOneBy([
            'commission' => $commission,
            'utilisateur' => $utilisateur,
        ]);

        if (null === $entity) {
            $ressource = new MembreCommission();
            $ressource->utilisateur = new Utilisateur($utilisateur);
            $ressource->commission = new Commission($commission);
        } else {
            $ressource = new MembreCommission($entity);
        }

        $ressource->roles = $data['roles'] ?? [];

        return $ressource;
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = [],
    ): bool
    {
        return MembreCommission::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [MembreCommission::class => true];
    }
}
