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
use App\Entity\CharteDemandeur;
use Doctrine\ORM\QueryBuilder;
use Override;

class CharteUtilisateurFilter extends AbstractFilter
{
    public const string PROPERTY = 'uidUtilisateurCharte';

    #[Override] protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if (!$operation->getClass() === CharteDemandeur::class || $property !== self::PROPERTY) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $demandeAlias = $queryNameGenerator->generateJoinAlias('demande');
        $utilisateurAlias = $queryNameGenerator->generateJoinAlias('utilisateur');
        $uidParam = $queryNameGenerator->generateParameterName('uid');

        $queryBuilder->join($alias . '.demande', $demandeAlias)
            ->innerJoin(sprintf('%s.demandeur', $demandeAlias), $utilisateurAlias)
            ->andWhere(sprintf('%s.uid = :%s', $utilisateurAlias, $uidParam))
            ->setParameter($uidParam, $value);
    }

    #[Override] public function getDescription(string $resourceClass): array
    {
        return [];
    }
}