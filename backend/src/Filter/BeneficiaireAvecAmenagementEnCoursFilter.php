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
use App\ApiResource\Utilisateur;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\PropertyInfo\Type;

class BeneficiaireAvecAmenagementEnCoursFilter extends AbstractFilter
{

    use ClockAwareTrait;

    public const string PROPERTY = 'benefAvecAmenagementEnCours';

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if (!$operation->getClass() === Utilisateur::class || $property !== self::PROPERTY) {
            return;
        }

        if ($value) {
            $alias = $queryBuilder->getRootAliases()[0];
            $benefAlias = $queryNameGenerator->generateJoinAlias('beneficiaires');
            $amenagementsAlias = $queryNameGenerator->generateJoinAlias('amenagements');
            $nowParam = $queryNameGenerator->generateParameterName('now');

            $queryBuilder->join(sprintf('%s.beneficiaires', $alias), $benefAlias)
                ->join(sprintf('%s.amenagements', $benefAlias), $amenagementsAlias)
                ->andWhere(sprintf('%1$s.debut <= :%2$s and (%1$s.fin is null or %1$s.fin > :%2$s)', $amenagementsAlias, $nowParam))
                ->andWhere(sprintf('%1$s.debut <= :%2$s and (%1$s.fin is null or %1$s.fin > :%2$s)', $benefAlias, $nowParam))
                ->setParameter($nowParam, $this->now());
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::PROPERTY => [
                'property' => self::class,
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'is_collection' => false,
                'openapi' => new Parameter(
                    name: self::class,
                    in: 'query',
                    description: self::class,
                ),
            ],
        ];
    }
}