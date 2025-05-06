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
use App\Entity\DecisionAmenagementExamens;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class EtatDecisionAmenagementFilter extends AbstractFilter
{
    protected const string PROPERTY = 'etatDecisionAmenagement';


    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $etats = [DecisionAmenagementExamens::ETAT_EDITE, DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE,
            DecisionAmenagementExamens::ETAT_VALIDE, DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS];

        if ($property !== self::PROPERTY) {
            return;
        }

        if (!is_string($value) || !in_array($value, $etats)) {
            $queryBuilder->andWhere('1=2');
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $decisionAlias = $queryNameGenerator->generateJoinAlias('decision');
        $decisionPlusRecenteAlias = $queryNameGenerator->generateJoinAlias('decision');
        $etatParam = $queryNameGenerator->generateParameterName('etat');


        $queryBuilder->join(sprintf('%s.decisionsAmenagementExamens', $rootAlias), $decisionAlias)
            ->leftJoin(sprintf('%s.decisionsAmenagementExamens', $rootAlias), $decisionPlusRecenteAlias,
                Join::WITH, sprintf('%s.debut > %s.debut', $decisionPlusRecenteAlias, $decisionAlias))
            ->andWhere(sprintf('%s.id is null', $decisionPlusRecenteAlias))
            ->andWhere(sprintf('%s.etat = :%s', $decisionAlias, $etatParam))
            ->setParameter($etatParam, $value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::PROPERTY => [
                'property' => self::PROPERTY,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => [
                    'description' => 'Etat avis ESE',
                    'name' => self::PROPERTY,
                    'type' => 'string',
                ],
            ],
        ];
    }
}