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
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\ApiResource\Tag;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DemandeDisciplineSportiveFilter extends AbstractFilter
{

    public const string PROPERTY = "discipline";

    public function __construct(private readonly IriConverterInterface $iriConverter,
                                ManagerRegistry                        $managerRegistry, ?LoggerInterface $logger = null,
                                ?array                                 $properties = null, ?NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }


    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== self::PROPERTY) {
            return;
        }

        $values = array_map(
            $this->getDisciplineIdFromIriOrId(...),
            match (true) {
                is_array($value) => $value,
                default => [$value]
            }
        );

        $alias = $queryBuilder->getRootAliases()[0];
        $reponseAlias = $queryNameGenerator->generateJoinAlias('reponse');
        $disciplineAlias = $queryNameGenerator->generateJoinAlias('discipline');
        $demandeurAlias = $queryNameGenerator->generateJoinAlias('demandeur');
        $disciplineIdsParameter = $queryNameGenerator->generateParameterName('disciplineIdsParameter');

        $queryBuilder->join(sprintf('%s.demandeur', $alias), $demandeurAlias)
            ->join(
                sprintf('%s.reponses', $demandeurAlias),
                $reponseAlias,
                Join::WITH,
                sprintf('%1$s.campagne = %2$s.campagne', $alias, $reponseAlias)
            )
            ->join(sprintf('%s.disciplinesSportives', $reponseAlias), $disciplineAlias)
            ->andWhere(sprintf('%1$s.demandeur = %2$s.repondant and %1$s.campagne = %2$s.campagne', $alias, $reponseAlias))
            ->andWhere($queryBuilder->expr()->in(sprintf('%s.id', $disciplineAlias), ':' . $disciplineIdsParameter))
            ->setParameter($disciplineIdsParameter, $values);

    }

    /**
     * @param string|int $val
     * @return int
     */
    public function getDisciplineIdFromIriOrId(string|int $val): int
    {
        if (is_numeric($val)) {
            $val = Tag::COLLECTION_URI . '/' . $val;
        }

        return ($this->iriConverter->getResourceFromIri($val))->id;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::PROPERTY => [
                'property' => self::PROPERTY,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => false,
                'openapi' => new Parameter(
                    name: self::PROPERTY,
                    in: 'query',
                    description: 'discipline sportive',
                ),
            ],
            self::PROPERTY . '[]' => [
                'property' => self::PROPERTY,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => true,
                'openapi' => new Parameter(
                    name: self::PROPERTY,
                    in: 'query',
                    description: 'discipline sportive',
                ),
            ],

        ];
    }
}