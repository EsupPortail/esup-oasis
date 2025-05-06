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

use ApiPlatform\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Override;

class CaseInsensitiveSearchFilter extends NestedFieldSearchFilter
{

    use PropertyHelperTrait;

    protected array $joins = [];

    #[Override] protected function doFilter(string $alias, string $currentResourceClass, string $targetField, array $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation, array $context): void
    {
        $exprs = [];
        foreach ($value as $val) {
            $param = $queryNameGenerator->generateParameterName($targetField);
            $exprs[] = $queryBuilder->expr()->like("lower(unaccent(" . $alias . '.' . $targetField . '))', 'unaccent(:' . $param . ')');
            $queryBuilder->setParameter($param, '%' . strtolower($val) . '%');
        }

        $orX = $queryBuilder->expr()->orX();
        $orX->addMultiple($exprs);

        $queryBuilder->andWhere($orX);
    }
}