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
use ApiPlatform\OpenApi\Model\Parameter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class LibCampusIntervenantFilter extends AbstractFilter
{

    protected function filterProperty(string                      $property, $value,
                                      QueryBuilder                $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator,
                                      string                      $resourceClass,
                                      ?Operation                  $operation = null,
                                      array                       $context = []): void
    {
        if ($property !== 'libelleCampus') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $joinAlias = $queryNameGenerator->generateJoinAlias('intervenant');
        $campusAlias = $queryNameGenerator->generateJoinAlias('campus');

        $condition = $queryBuilder->expr()->like("lower(unaccent(" . $campusAlias . '.libelle' . '))', 'unaccent(?1)');

        $queryBuilder
            ->join($alias . '.intervenant', $joinAlias)
            ->join($joinAlias . '.campuses', $campusAlias)
            ->andWhere($condition)
            ->setParameter('1', "%" . strtolower($value) . "%");
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'libelleCampus' => [
                'property' => 'libelleCampus',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => new Parameter(
                    name: 'libelleCampus',
                    in: 'query',
                    description: "Recherche sur le libelle de campus de l'intervenant",
                ),
            ],
        ];
    }
}