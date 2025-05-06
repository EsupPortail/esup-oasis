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
use App\ApiResource\Utilisateur;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ProfilBeneficiaireFilter extends AbstractFilter
{
    use ClockAwareTrait;

    public function __construct(private readonly IriConverterInterface $iriConverter, ManagerRegistry $managerRegistry,
                                ?LoggerInterface                       $logger = null, ?array $properties = null,
                                ?NameConverterInterface                $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if (!$operation->getClass() === Utilisateur::class || $property !== 'profil') {
            return;
        }

        $profil = $this->iriConverter->getResourceFromIri($value);

        $alias = $queryBuilder->getRootAliases()[0];
        $bAlias = $queryNameGenerator->generateJoinAlias('beneficiaires');
        $profilAlias = $queryNameGenerator->generateJoinAlias('profil');
        $profilIdParam = $queryNameGenerator->generateParameterName('profilId');
        $nowParam = $queryNameGenerator->generateParameterName('now');

        $withCondition = ':' . $nowParam . ' >= ' . $bAlias . '.debut and (:' . $nowParam . ' < ' . $bAlias . '.fin or ' . $bAlias . '.fin is null)';

        $queryBuilder->join($alias . '.beneficiaires', $bAlias)
            ->join($bAlias . '.profil', $profilAlias)
            ->andWhere($withCondition)
            ->andWhere($profilAlias . '.id = :' . $profilIdParam)
            ->setParameter($profilIdParam, $profil->id)
            ->setParameter($nowParam, $this->now());
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'profil' => [
                'property' => 'profil',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'openapi' => new Parameter(
                    name: 'profil',
                    in: 'query',
                    description: 'Recherche sur le profil bénéficiaire',
                ),
            ],
        ];
    }
}