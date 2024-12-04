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
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class NomIntervenantFilter extends AbstractFilter
{

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if ($property !== 'nomIntervenant') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $joinAlias = $queryNameGenerator->generateJoinAlias('intervenant');
        $utilisateurAlias = $queryNameGenerator->generateJoinAlias('utilisateur');

        $condition = $queryBuilder->expr()->like("lower(unaccent(" . $utilisateurAlias . '.nom' . '))', 'unaccent(?1)');

        $queryBuilder
            ->join($alias . '.intervenant', $joinAlias)
            ->join($joinAlias . '.utilisateur', $utilisateurAlias)
            ->andWhere($condition)
            ->setParameter('1', "%" . strtolower($value) . "%");

    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'nomIntervenant' => [
                'property' => 'nomIntervenant',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => [
                    'description' => "Recherche sur le nom de l'intervenant",
                    'name' => 'nomIntervenant',
                    'type' => 'string',
                ],
            ],
        ];
    }
}