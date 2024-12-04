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
use App\Entity\PieceJointeBeneficiaire;
use Doctrine\ORM\QueryBuilder;
use Override;


class PieceJointeBeneficiaireUidFilter extends AbstractFilter
{

    public const string PROPERTY = 'uidBeneficiaire';

    #[Override] protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                                  QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                                  ?Operation                  $operation = null, array $context = []): void
    {
        if (!$operation->getClass() == PieceJointeBeneficiaire::class || $property !== self::PROPERTY) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $utilisateurAlias = $queryNameGenerator->generateJoinAlias('utilisateur');
        $uidParam = $queryNameGenerator->generateParameterName('uid');

        $queryBuilder->innerJoin(sprintf('%s.beneficiaire', $alias), $utilisateurAlias)
            ->andWhere(sprintf('%s.uid = :%s', $utilisateurAlias, $uidParam))
            ->setParameter($uidParam, $value);
    }

    #[Override] public function getDescription(string $resourceClass): array
    {
        return [];
    }
}