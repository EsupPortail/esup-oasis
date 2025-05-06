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
use App\Entity\TypeEvenement;
use App\Entity\Utilisateur;
use Doctrine\ORM\QueryBuilder;

class RenfortFilter extends AbstractFilter
{
    public const string PROPERTY = 'renfortfilter';

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if (!$operation->getClass() === Utilisateur::class || $property !== self::PROPERTY) {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];
        $typeId = TypeEvenement::TYPE_RENFORT;

        $queryBuilder->join($alias . '.intervenant', 'i')
            ->join('i.typesEvenements', 'types')
            ->join($alias . '.services', 'services')
            ->andWhere('types.id = :typeRenfort')
            ->setParameter('typeRenfort', $typeId);
    }

    public function getDescription(string $resourceClass): array
    {
        //on ne veut pas le montrer!
        return [];
    }
}