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
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\ApiResource\PeriodeRH;
use App\Entity\Evenement;
use App\Entity\InterventionForfait;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ActivitePeriodeFilter extends AbstractFilter
{
    public function __construct(private readonly IriConverterInterface         $iriConverter,
                                private readonly PropertyAccessorInterface     $propertyAccessor,
                                private readonly IdentifiersExtractorInterface $identifiersExtractor,
                                ManagerRegistry                                $managerRegistry,
                                ?LoggerInterface                               $logger = null,
                                ?array                                         $properties = null,
                                ?NameConverterInterface                        $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator,
                                      string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $entityClass = $operation->getClass();

        if (!in_array($entityClass, [Evenement::class, InterventionForfait::class]) || $property !== 'periode') {
            return;
        }

        /**
         * Si on est sur les interventions au forfait, on prend la période directement liée
         * Si on est sur les événements, on prend:
         *  => si période fermée, les événements liés
         *  => si période non fermée, les événements antérieurs à la date de fin de période et non associés à une période
         */

        //cas simple, InterventionForfait : on appelle le SearchFilter d'APIP
        if ($entityClass === InterventionForfait::class) {
            $searchFilter = new SearchFilter($this->managerRegistry, $this->iriConverter, $this->propertyAccessor,
                $this->logger, ['periode' => 'exact'], $this->identifiersExtractor, $this->nameConverter);

            $searchFilter->filterProperty('periode', $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
            return;
        }

        //cas pénible, Evenement...
        $alias = $queryBuilder->getRootAliases()[0];
        $values = is_array($value) ? $value : [$value];
        $whereConditions = [];
        foreach ($values as $value) {
            /**
             * @var PeriodeRH $periode
             */
            $periode = $this->iriConverter->getResourceFromIri($value);
            $periodeAlias = $queryNameGenerator->generateJoinAlias('periodePriseEnCompteRH');
            $periodeIdParameter = $queryNameGenerator->generateParameterName('periodeId');

            $queryBuilder->setParameter($periodeIdParameter, $periode->id);

            if ($periode->envoyee) {
                $queryBuilder->leftJoin($alias . '.periodePriseEnCompteRH', $periodeAlias,
                    Join::WITH, $periodeAlias . '.id = :' . $periodeIdParameter);
                $whereConditions[] = $periodeAlias . '.id is not null';
            } else {
                $queryBuilder->join('App\Entity\PeriodeRH', $periodeAlias,
                    Join::WITH, $periodeAlias . '.id = :' . $periodeIdParameter);

                $whereCondition = $alias . '.periodePriseEnCompteRH is null';
                $whereCondition .= ' and ' . $periodeAlias . '.fin > ' . $alias . '.fin';
                $whereConditions[] = $whereCondition;
            }
        }
        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$whereConditions));
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'periode' => [
                'property' => 'periode',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
                'openapi' => new Parameter(
                    name: 'periode',
                    in: 'query',
                    description: "Période RH concernée",
                ),
            ],
            'periode[]' => [
                'property' => 'periode',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => true,
                'openapi' => new Parameter(
                    name: 'periode',
                    in: 'query',
                    description: "Période RH concernée",
                ),
            ],
        ];
    }
}