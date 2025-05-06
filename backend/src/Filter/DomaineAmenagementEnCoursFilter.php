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
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DomaineAmenagementEnCoursFilter extends AbstractFilter
{

    public function __construct(private readonly UtilisateurAmenagementEnCoursFilterHelper $helper,
                                ManagerRegistry                                            $managerRegistry,
                                ?LoggerInterface                                           $logger = null,
                                ?array                                                     $properties = null,
                                ?NameConverterInterface                                    $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, ['examens', 'pedagogique', 'aideHumaine'], $nameConverter);
    }

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if (!in_array($property, $this->getProperties())) {
            return;
        }

        $this->helper->ajouterJointuresDomaine($queryBuilder, $queryNameGenerator, $value, $property);

    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->getProperties() as $property) {
            $description[$property] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'is_collection' => false,
                'openapi' => new Parameter(
                    name: $property,
                    in: 'query',
                    description: $property,
                ),
            ];
        }
        return $description;
    }
}