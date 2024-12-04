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

namespace App\Filter;

use ApiPlatform\Doctrine\Common\Filter\OrderFilterTrait;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class CaseInsensitiveOrderFilter extends AbstractFilter
{
    use OrderFilterTrait;

    public function __construct(ManagerRegistry          $managerRegistry,
                                string                   $orderParameterName = 'order',
                                ?LoggerInterface         $logger = null,
                                ?array                   $properties = null,
                                ?NameConverterInterface  $nameConverter = null,
                                private readonly ?string $orderNullsComparison = null)
    {
        $this->orderParameterName = $orderParameterName;
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    /**
     * @param string $property
     * @param                             $value
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param Operation|null $operation
     * @param array $context
     * @return void
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator,
                                      string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== $this->orderParameterName) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        foreach ($value as $prop => $direction) {
            $join = sprintf('upper(unaccent(%s.%s))', $alias, $prop);
            $fieldAlias = $queryNameGenerator->generateParameterName('order');
            $queryBuilder->addSelect($join . ' as hidden ' . $fieldAlias);
            $queryBuilder->addOrderBy($fieldAlias, $direction ?? 'asc');
        }

    }
}