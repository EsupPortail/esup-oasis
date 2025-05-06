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

class NomGestionnaireFilter extends AbstractFilter
{

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if ($property !== 'nomGestionnaire') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $beneficiaireAlias = $queryNameGenerator->generateJoinAlias('beneficiaires');
        $gestionnaireAlias = $queryNameGenerator->generateJoinAlias('gestionnaire');

        $condition = $queryBuilder->expr()->like("lower(unaccent(" . $gestionnaireAlias . '.nom' . '))', 'unaccent(?1)');

        $queryBuilder
            ->join($alias . '.beneficiaires', $beneficiaireAlias)
            ->join($beneficiaireAlias . '.gestionnaire', $gestionnaireAlias)
            ->andWhere($condition)
            ->setParameter('1', "%" . strtolower($value) . "%");
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'nomGestionnaire' => [
                'property' => 'nomGestionnaire',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => new Parameter(
                    name: 'nomGestionnaire',
                    in: 'query',
                    description: 'Recherche sur le nom du gestionnaire du bénéficiaire',
                ),
            ],
        ];
    }
}