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

namespace App\State\Parametre;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Parametre;
use App\Service\ParametreService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class ParametreProvider implements ProviderInterface
{
    function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private ProviderInterface $itemProvider,
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private ProviderInterface $collectionProvider,
        private ParametreService $parametreService,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $constParams = $this->parametreService->getConstantParams();

        /**
         * Collection complete : on ignore la pagination...inutile pour si peu de lignes
         */
        if ($operation instanceof GetCollection) {
            $results = $this->collectionProvider->provide($operation, $uriVariables, $context);

            // le nombre de paramètres est réduit, on peut se permettre de tout récupérer et d'ajouter les paramètres constants
            $results = array_merge(iterator_to_array($results), $constParams);

            return array_map(fn($entity) => new Parametre($entity), $results);
        }

        /**
         * Un élément en particulier, on récupère l'élément constant s'il existe
         */
        $constant = array_find(
            $constParams,
            fn(\App\Entity\Parametre $param) => $param->getCle() === $uriVariables['cle'],
        );

        $entity = match ($constant) {
            null => $this->itemProvider->provide($operation, $uriVariables, $context),
            default => $constant,
        };

        return match ($entity) {
            null => null,
            default => new Parametre($entity),
        };
    }
}
