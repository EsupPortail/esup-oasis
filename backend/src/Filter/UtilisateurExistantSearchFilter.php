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

class UtilisateurExistantSearchFilter extends AbstractFilter
{

    protected const string PROPERTY = 'recherche';

    /**
     * @param string $property
     * @param string $value
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
        if (static::PROPERTY !== $property) {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];
        $strExprs = [];
        $intExprs = [];

        foreach ($this->properties as $property => $type) {
            if (($type ?? 'string') === 'string') {
                $strExprs[] = $queryBuilder->expr()->like("lower(unaccent(" . $alias . '.' . $property . '))', 'unaccent(:strValue)');
            } else {
                if (is_numeric($value)) {
                    $intExprs[] = sprintf('%s.%s = :intValue', $alias, $property);
                }
            }
        }
        $orX = $queryBuilder->expr()->orX();
        $orX->addMultiple([...$strExprs, ...$intExprs]);

        $queryBuilder->andWhere($orX);
        if (count($strExprs) > 0) {
            $queryBuilder->setParameter('strValue', "%" . strtolower(trim($value)) . "%");
        }
        if (count($intExprs) > 0) {
            $queryBuilder->setParameter('intValue', $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'recherche' => [
                'property' => static::PROPERTY,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => [
                    'description' => 'Recherche sur (' . implode(', ', $this->getProperties()) . ')',
                    'name' => static::PROPERTY,
                    'type' => 'string',
                ],
            ],
        ];
    }
}