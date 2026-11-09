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

namespace App\State\Reponse;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Reponse;
use App\Repository\DemandeRepository;
use App\State\OptionReponse\OptionReponseProvider;
use Exception;

readonly class ReponseProvider implements ProviderInterface
{
    public function __construct(
        private DemandeRepository $demandeRepository,
        private OptionReponseProvider $optionReponseProvider,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        //input url : {demandeId} + {questionId}
        $demande = $this->demandeRepository->find($uriVariables['demandeId']);

        $reponses = array_filter(
            $demande->getDemandeur()->getReponses()->toArray(),
            static fn(\App\Entity\Reponse $reponse) => (
                $reponse->getCampagne() === $demande->getCampagne()
                && $reponse->getQuestion()->getId() === (int) $uriVariables['questionId']
            ),
        );

        return match (true) {
            empty($reponses) => null,
            default => $this->transform(current($reponses)),
        };
    }

    /**
     * @param \App\Entity\Reponse $entity
     * @return mixed
     * @throws Exception
     */
    public function transform(\App\Entity\Reponse $entity): mixed
    {
        $resource = new Reponse($entity);

        $resource->optionsChoisies = $this->optionReponseProvider->getOptionsForReponse($entity);

        return $resource;
    }
}
