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
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class TypeAmenagementEnCoursFilter extends AbstractFilter
{

    protected const string PROPERTY = 'type';

    public function __construct(private readonly UtilisateurAmenagementEnCoursFilterHelper $helper,
                                ManagerRegistry                                            $managerRegistry,
                                ?LoggerInterface                                           $logger = null,
                                ?array                                                     $properties = null,
                                ?NameConverterInterface                                    $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if ($property !== self::PROPERTY) {
            return;
        }

        $this->helper->ajouterJointuresType($queryBuilder, $queryNameGenerator, $value);

    }

    public function getDescription(string $resourceClass): array
    {
        return $this->helper->getDescription(self::PROPERTY);
    }
}