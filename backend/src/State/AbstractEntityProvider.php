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

namespace App\State;

use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\PeriodeRH;
use ArrayIterator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractEntityProvider extends AbstractTransformer implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private readonly ProviderInterface $itemProvider,
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private readonly ProviderInterface $collectionProvider,
    )
    {
    }

    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return PeriodeRH|PeriodeRH[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {

            if (array_key_exists('postfiltrage', $context) && $context['postfiltrage'] === true) {
                //pagination?
                $postfiltrage = true;
                $operation = $operation->withPaginationEnabled(false);
            }

            if ($pagination = (($operation->getPaginationEnabled() ?? false) || array_key_exists('page', $context['filters'] ?? []))) {
                $itemsPerPage = (int)$context['filters']['itemsPerPage'] ?? 30;
                $page = $context['filters']['page'] ?? 1;
                $first = (($page - 1) * $itemsPerPage);
            }

            $data = $this->collectionProvider->provide(
                $operation->withClass($operation->getStateOptions()?->getEntityClass() ?? $operation->getClass()),
                $uriVariables,
                $context);

            $processed = [];

            foreach ($data as $item) {
                try {
                    $processed[] = $this->transform($item);
                } catch (ItemNotFoundException) {
                    //condition non remplie dans transform()
                    //NOOP, ici on l'ignore dans la liste
                }
            }

            //On (re-)pagine si nécessaire!
            if ($pagination && !($postfiltrage ?? false)) {
                return new TraversablePaginator(new ArrayIterator($processed), $data->getCurrentPage(), $data->getItemsPerPage(), $data->getTotalItems());
            }
            return $processed;
        }

        $data = $this->itemProvider->provide(
            $operation->withClass($operation->getStateOptions()->getEntityClass()),
            $uriVariables,
            $context
        );

        if (null === $data) {
            throw new NotFoundHttpException();
        }

        return $this->transform($data);
    }

    protected function registerTransformations(): void
    {
        $this->transformerService->addTransformation($this->getEntityClass(), $this->getResourceClass(), $this->transform(...));
    }

    abstract protected function getResourceClass(): string;

    abstract protected function getEntityClass(): string;

}