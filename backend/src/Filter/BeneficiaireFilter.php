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

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\ApiResource\Utilisateur;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Clock\ClockAwareTrait;

class BeneficiaireFilter extends AbstractFilter
{
    use ClockAwareTrait;

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if (!$operation->getClass() === Utilisateur::class || $property !== 'beneficiairefilter') {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];
        $beneficiaireAlias = $queryNameGenerator->generateJoinAlias('beneficiaire');
        $nowParam = $queryNameGenerator->generateParameterName('now');

        $withCondition = ':' . $nowParam . ' >= ' . $beneficiaireAlias . '.debut and (:' . $nowParam . ' < ' . $beneficiaireAlias . '.fin or ' . $beneficiaireAlias . '.fin is null)';

        $queryBuilder->join($alias . '.beneficiaires', $beneficiaireAlias, Join::WITH, $withCondition)
            ->setParameter($nowParam, $this->now());
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}