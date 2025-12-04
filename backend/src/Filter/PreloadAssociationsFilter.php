<?php

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class PreloadAssociationsFilter extends AbstractFilter
{
    public const string PROPERTY = 'preloadAssociations';

    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== self::PROPERTY) {
            return;
        }

        //On parcourt les valeurs et on ajout des join() et des select() pour forcer le chargement des données des associations
        if (!is_array($value)) {
            return;
        }

        $aliases = [
            'root' => $queryBuilder->getRootAliases()[0],
        ];

        foreach ($value as $associationName => $association) {
            $aliases[$associationName] = $queryNameGenerator->generateJoinAlias($associationName);
            $queryBuilder->leftJoin($aliases[$association['sourceEntity']] . '.' . $association['relationName'], $aliases[$associationName]);
            $queryBuilder->addSelect($aliases[$associationName]);
        }

        return;

    }

    public function getDescription(string $resourceClass): array
    {
        return [];//tambouille interne
    }
}