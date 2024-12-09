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
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class PeriodeIntervenantFilter extends AbstractFilter
{

    public const string PROPERTY = 'periodeIntervenantFitler';

    /**
     * @inheritDoc
     */
    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if ($property !== self::PROPERTY) {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];
        $evenementAlias = $queryNameGenerator->generateJoinAlias('evenements');
        $intervenantAlias = $queryNameGenerator->generateJoinAlias('intervenant');

        $queryBuilder->join($alias . '.evenements', $evenementAlias)
            ->join($evenementAlias . '.intervenant', $intervenantAlias, Join::WITH, $intervenantAlias . '.uid=:uid')
            ->setParameter('uid', $value);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(string $resourceClass): array
    {
        return []; //filtre purement interne, non exposé
    }
}