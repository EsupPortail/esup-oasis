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

namespace App\State\PieceJointeBeneficiaire;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Charte;
use App\ApiResource\PieceJointeBeneficiaire;
use App\Filter\PieceJointeBeneficiaireUidFilter;
use App\State\MappedCollectionPaginator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PieceJointeBeneficiaireProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private ProviderInterface $itemProvider,
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private ProviderInterface $collectionProvider,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            //ajout d'un filtre sur utilisateur!
            $context['filters'][PieceJointeBeneficiaireUidFilter::PROPERTY] = $uriVariables['uid'];
            unset($uriVariables['uid']);

            $results = $this->collectionProvider->provide($operation, $uriVariables, $context);
            assert($results instanceof PaginatorInterface);
            return new MappedCollectionPaginator($results, fn($entity) => new PieceJointeBeneficiaire($entity));
        }

        //on reconstruit une opération qui ne va pas exploser en vol
        $relevantVariables = ['id' => $uriVariables['id']];

        $link = new Link(parameterName: 'id', fromClass: PieceJointeBeneficiaire::class, identifiers: ['id']);
        $relevantOperation = new (get_class($operation))()
            ->withClass(PieceJointeBeneficiaire::class)
            ->withStateOptions($operation->getStateOptions())
            ->withUriVariables([$link]);

        $entity = $this->itemProvider->provide(
            operation: $relevantOperation,
            uriVariables: $relevantVariables,
            context: $context,
        );
        if ($entity?->getBeneficiaire()?->getUid() !== $uriVariables['uid']) {
            return null;
        }
        return match ($entity) {
            null => null,
            default => new PieceJointeBeneficiaire($entity),
        };
    }
}
