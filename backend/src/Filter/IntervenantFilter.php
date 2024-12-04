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
use App\Entity\Utilisateur;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Clock\ClockAwareTrait;

class IntervenantFilter extends AbstractFilter
{
    use ClockAwareTrait;

    public const string PROPERTY = 'intervenantfilter';

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if (!$operation->getClass() == Utilisateur::class || $property !== self::PROPERTY) {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];

        //on allège au maximum pour ne pas être bloqué sur des opérations décalées dans le temps
        $queryBuilder->join($alias . '.intervenant', 'i')
            ->innerJoin('i.typesEvenements', 'type');
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}