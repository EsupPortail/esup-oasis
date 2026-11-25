<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class PreloadAssociationsFilter extends AbstractFilter
{
    public const string PROPERTY = 'preloadAssociations';

    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ($property !== self::PROPERTY) {
            return;
        }

        //On parcourt les valeurs et on ajout des join() et des select() pour forcer le chargement des données des associations
        if (!is_array($value)) {
            return;
        }

        // Ce filtre est purement interne aux Providers : on ignore toute valeur issue directement des query params HTTP
        if (
            isset($context['request'])
            && $context['request']->query->has(self::PROPERTY)
            && $context['request']->query->all()[self::PROPERTY] === $value
        ) {
            return;
        }

        $rootEntityClass = (
            $operation?->getStateOptions()?->getEntityClass() ?? $operation?->getClass() ?? $resourceClass
        );
        $aliases = [
            'root' => $queryBuilder->getRootAliases()[0],
        ];
        $entityClasses = [
            'root' => $rootEntityClass,
        ];

        foreach ($value as $associationName => $association) {
            if (!is_array($association)) {
                continue;
            }
            $sourceEntity = $association['sourceEntity'] ?? null;
            $relationName = $association['relationName'] ?? null;
            if (
                !is_string($sourceEntity)
                || !is_string($relationName)
                || !isset($aliases[$sourceEntity], $entityClasses[$sourceEntity])
            ) {
                continue;
            }
            $metadata = $this->getClassMetadata($entityClasses[$sourceEntity]);
            if (!$metadata->hasAssociation($relationName)) {
                continue;
            }
            $aliases[$associationName] = $queryNameGenerator->generateJoinAlias($associationName);
            $entityClasses[$associationName] = $metadata->getAssociationTargetClass($relationName);
            if (($association['joinType'] ?? 'LEFT') === 'LEFT') {
                $queryBuilder->leftJoin(
                    $aliases[$sourceEntity] . '.' . $relationName,
                    $aliases[$associationName],
                );
            } else {
                $queryBuilder->innerJoin(
                    $aliases[$sourceEntity] . '.' . $relationName,
                    $aliases[$associationName],
                );
            }
            $queryBuilder->addSelect($aliases[$associationName]);
        }

        return;
    }

    public function getDescription(string $resourceClass): array
    {
        return []; //tambouille interne
    }
}
