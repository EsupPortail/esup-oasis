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
use App\Entity\Tag;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class BeneficiaireActifTagFilter extends AbstractFilter
{
    use BeneficiaireActifAwareFilterTrait;

    public function __construct(private readonly IriConverterInterface $iriConverter,
                                ManagerRegistry                        $managerRegistry, ?LoggerInterface $logger = null,
                                ?array                                 $properties = null, ?NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }


    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator,
                                      string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!in_array($property, array_keys($this->getProperties()))) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $beneficiaireAlias = $this->filterBeneficiairesActifs($queryBuilder, $queryNameGenerator, $alias);

        $values = match (is_array($value)) {
            true => array_map(
                fn($val) => $this->getTagEntityFromIriOrId($val),
                $value
            ),
            default => [$this->getTagEntityFromIriOrId($value)]
        };

        $tagAlias = $queryNameGenerator->generateJoinAlias('tags');
        $tagIdsParameter = $queryNameGenerator->generateParameterName('tagIds');

        $queryBuilder->join(sprintf('%s.tags', $beneficiaireAlias), $tagAlias)
            ->andWhere($queryBuilder->expr()->in(sprintf('%s.id', $tagAlias), ':' . $tagIdsParameter))
            ->setParameter($tagIdsParameter, array_map(fn(Tag $tag) => $tag->getId(), $values));

    }

    //pas un champ ni une association...wtf?
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
                    description: $value['description'] ?? $property,
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
                    description: $value['description'] ?? $property,
                ),
            ];
        }
        return $description;
    }

    private function getTagEntityFromIriOrId(string|int $val): Tag
    {
        if (is_numeric($val)) {
            $val = \App\ApiResource\Tag::COLLECTION_URI . '/' . $val;
        }

        $resource = $this->iriConverter->getResourceFromIri($val);

        return $this->managerRegistry->getManagerForClass(Tag::class)->getRepository(Tag::class)->find($resource->id);
    }


}