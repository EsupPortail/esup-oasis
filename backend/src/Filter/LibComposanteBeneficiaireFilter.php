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
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\PropertyInfo\Type;

class LibComposanteBeneficiaireFilter extends AbstractFilter
{

    use ClockAwareTrait;

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if ($property !== 'libelleComposante') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $inscriptionAlias = $queryNameGenerator->generateJoinAlias('inscriptions');
        $formationAlias = $queryNameGenerator->generateJoinAlias('formation');
        $composanteALias = $queryNameGenerator->generateJoinAlias('composante');

        $condition = $queryBuilder->expr()->like("lower(unaccent(" . $composanteALias . '.libelle' . '))', 'unaccent(?1)');

        $queryBuilder
            ->join($alias . '.inscriptions', $inscriptionAlias)
            ->join($inscriptionAlias . '.formation', $formationAlias)
            ->join($formationAlias . '.composante', $composanteALias)
            ->andWhere($condition)
            ->andWhere()
            ->setParameter('1', "%" . strtolower($value) . "%");
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'libelleComposante' => [
                'property' => 'libelleComposante',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => [
                    'description' => "Recherche sur le libelle de composante d'inscription",
                    'name' => 'libelleCampus',
                    'type' => 'string',
                ],
            ],
        ];
    }
}