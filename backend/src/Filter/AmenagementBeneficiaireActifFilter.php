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
use App\Entity\Amenagement;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Override;
use Symfony\Component\Clock\ClockAwareTrait;

class AmenagementBeneficiaireActifFilter extends AbstractFilter
{
    use ClockAwareTrait;

    public const string PROPERTY = 'amenagementBeneficiaireActif';

    #[Override] public function getDescription(string $resourceClass): array
    {
        return [];
    }

    #[Override] protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                                  QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                                  ?Operation                  $operation = null, array $context = []): void
    {
        if (!$operation->getClass() === Amenagement::class || $property !== self::PROPERTY) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $benefAlias = $queryNameGenerator->generateJoinAlias('beneficiaires');
        $nowParam = $queryNameGenerator->generateParameterName('now');

        $withCondition = ':' . $nowParam . ' >= ' . $benefAlias . '.debut and (:' . $nowParam . ' < ' . $benefAlias . '.fin or ' . $benefAlias . '.fin is null)';
        $queryBuilder->join($alias . '.beneficiaires', $benefAlias, Join::WITH, $withCondition)
            ->setParameter($nowParam, $this->now());

    }
}