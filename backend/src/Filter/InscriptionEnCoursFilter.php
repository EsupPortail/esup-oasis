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
use App\Entity\Formation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\PropertyInfo\Type;

class InscriptionEnCoursFilter extends AbstractFilter
{
    use ClockAwareTrait;

    public const string PROPERTY = 'avecInscriptions';

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== self::PROPERTY || $value !== 'true' || $resourceClass !== Formation::class) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $inscriptionsAlias = $queryNameGenerator->generateJoinAlias('inscriptions');
        $nowParam = $queryNameGenerator->generateParameterName('now');
        $now = $this->now();

        $queryBuilder->join(sprintf('%s.inscriptions', $alias), $inscriptionsAlias)
            ->andWhere(sprintf('%1$s.fin > :%2$s', $inscriptionsAlias, $nowParam))
            ->setParameter($nowParam, $now);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::PROPERTY => [
                'property' => self::PROPERTY,
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'openapi' => new Parameter(
                    name: 'aValider',
                    in: 'query',
                    description: 'uniquement les formations avec inscription',
                ),
            ],
        ];
    }
}