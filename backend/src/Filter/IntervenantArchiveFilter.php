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
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\PropertyInfo\Type;

class IntervenantArchiveFilter extends AbstractFilter
{
    use ClockAwareTrait;

    public function getDescription(string $resourceClass): array
    {
        return [
            'intervenantArchive' => [
                'property' => 'intervenantArchive',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'openapi' => new Parameter(
                    name: 'intervenantArchive',
                    in: 'query',
                    description: "filtre sur l'état de l'intervenant à l'instant T",
                ),
            ],
        ];
    }

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if ($property !== 'intervenantArchive') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $intervenantAlias = $queryNameGenerator->generateJoinAlias('intervenant');
        $nowParam = $queryNameGenerator->generateParameterName('now');

        $queryBuilder->join($alias . '.intervenant', $intervenantAlias);
        if ($value === 'false') {
            $queryBuilder->andWhere($intervenantAlias . '.fin is null or ' . $intervenantAlias . '.fin > :' . $nowParam);
        } else {
            $queryBuilder->andWhere($intervenantAlias . '.fin is not null and ' . $intervenantAlias . '.fin <= :' . $nowParam);
        }
        $queryBuilder->setParameter($nowParam, $this->now());
    }
}