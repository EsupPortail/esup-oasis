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
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class IntervenantDisponibleFilter extends AbstractFilter
{

    public function getDescription(string $resourceClass): array
    {
        return [
            'creneau[debut]' => [
                'property' => 'creneau',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => new Parameter(
                    name: 'creneau',
                    in: 'query',
                    description: 'début du créneau',
                ),
            ],
            'creneau[fin]' => [
                'property' => 'creneau',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => new Parameter(
                    name: 'creneau',
                    in: 'query',
                    description: 'fin du créneau',
                ),
            ],
        ];
    }

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if ($property !== 'creneau') {
            return;
        }
        if (!array_key_exists('debut', $value) || !array_key_exists('fin', $value)) {
            //on sort en silence. log, message...erreur?
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];
        $intervenantAlias = $queryNameGenerator->generateJoinAlias('intervenant');
        $evenementsAlias = $queryNameGenerator->generateJoinAlias('evenements');

        $queryBuilder
            ->join($alias . '.intervenant', $intervenantAlias)
            ->leftJoin($intervenantAlias . '.interventions', $evenementsAlias,
                Join::WITH,
                $evenementsAlias . '.debut between :debut and :fin or :debut between ' . $evenementsAlias . '.debut and ' . $evenementsAlias . '.fin')
            ->andWhere($evenementsAlias . '.id is null')
            ->setParameter('debut', $value['debut'])
            ->setParameter('fin', $value['fin']);


    }
}