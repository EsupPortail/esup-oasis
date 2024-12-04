<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\PropertyInfo\Type;

class CampagneNonArchiveeFilter extends AbstractFilter
{

    use ClockAwareTrait;

    public const string PROPERTY = 'archivees';

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== self::PROPERTY || !is_string($value) || $value != 'false') {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $campagneAlias = $queryNameGenerator->generateJoinAlias('campagne');

        $queryBuilder->join(sprintf('%s.campagne', $rootAlias), $campagneAlias)
            ->andWhere(sprintf('%1$s.dateArchivage IS NULL or %1$s.dateArchivage >= :now', $campagneAlias))
            ->setParameter('now', $this->now());
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'archivees' => [
                'property' => "archivees",
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'is_collection' => false,
                'openapi' => [
                    'description' => "inclure les demandes des campagnes archivées?",
                    'name' => 'archivees',
                    'type' => 'boolean',
                ],
            ]
        ];
    }
}