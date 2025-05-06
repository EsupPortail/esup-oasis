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
use ApiPlatform\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class NestedUtilisateurFilter extends AbstractFilter
{

    use PropertyHelperTrait;

    public function __construct(private readonly IriConverterInterface $iriConverter, ManagerRegistry $managerRegistry,
                                ?LoggerInterface                       $logger = null, ?array $properties = null,
                                ?NameConverterInterface                $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    /**
     * @param string $property
     * @param                             $value
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param Operation|null $operation
     * @param array $context
     * @return void
     */
    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if (!in_array($property, array_keys($this->getProperties()))) {
            return;
        }
        if (!is_array($value)) {
            $value = [$value];
        }
        if (empty($value)) {
            return;
        }
        $utilisateurs = array_map(fn($val) => $this->iriConverter->getResourceFromIri($val), $value);

        $alias = $queryBuilder->getRootAliases()[0];
        $joinAlias = $queryNameGenerator->generateJoinAlias($property);
        //$queryBuilder->join($alias . '.' . $property, $joinAlias);
        $uidParams = [];
        foreach ($utilisateurs as $utilisateur) {
            $uidParam = $queryNameGenerator->generateParameterName('uid');
            $uidParams[] = $uidParam;
            $queryBuilder->setParameter($uidParam, $utilisateur->uid);
        }
        $uidInList = implode(' , ', array_map(fn($uidParam) => ':' . $uidParam, $uidParams));

        $targetField = $this->getProperties()[$property];
        $currentResourceClass = $resourceClass;

        while ($this->isPropertyNested($targetField, $currentResourceClass)) {
            $metadata = $this->getClassMetadata($currentResourceClass);
            $parts = explode('.', $targetField);
            $current = array_shift($parts);

            $nextAlias = $queryNameGenerator->generateJoinAlias($current);
            $queryBuilder->join(sprintf('%s.%s', $alias, $current), $nextAlias);

            $alias = $nextAlias;
            $targetField = implode('.', $parts);
            $currentResourceClass = $metadata->getAssociationTargetClass($current);
        }
        $queryBuilder->join(sprintf('%s.%s', $alias, $targetField), $joinAlias);

        $queryBuilder->andWhere($joinAlias . '.uid in (' . $uidInList . ')');

    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->getProperties() as $property => $value) {
            $description[$property] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => false,
                'openapi' => new Parameter(
                    name: $property,
                    in: 'query',
                    description: 'IRI utilisateur',
                ),
            ];
            $description[$property . '[]'] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => true,
                'openapi' => new Parameter(
                    name: $property,
                    in: 'query',
                    description: 'IRIs utilisateur',
                )
            ];
        }
        return $description;
    }


}