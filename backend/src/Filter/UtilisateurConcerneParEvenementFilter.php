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
use App\ApiResource\Evenement;
use App\ApiResource\Utilisateur;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class UtilisateurConcerneParEvenementFilter extends AbstractFilter
{

    public function __construct(private readonly IriConverterInterface $iriConverter, ManagerRegistry $managerRegistry,
                                ?LoggerInterface                       $logger = null, ?array $properties = null,
                                ?NameConverterInterface                $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    /**
     * @param string $property
     * @param Utilisateur|string $value
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param Operation|null $operation
     * @param array $context
     * @return void
     */
    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if (!$operation->getClass() === Evenement::class || $property !== 'utilisateurConcerne') {
            return;
        }

        if (!$value instanceof Utilisateur) {
            $utilisateur = $this->iriConverter->getResourceFromIri($value);
        } else {
            $utilisateur = $value;
        }

        if (in_array(\App\Entity\Utilisateur::ROLE_PLANIFICATEUR, $utilisateur->roles)) {
            return;
        }

        //on ajoute les jointures pour bénéficiaire ou intervenant
        $alias = $queryBuilder->getRootAliases()[0];
        $intervenantAlias = $queryNameGenerator->generateJoinAlias('intervenant');
        $beneficiairesAlias = $queryNameGenerator->generateJoinAlias('beneficiaires');
        $utilisateurAlias = $queryNameGenerator->generateJoinAlias('utilisateur');
        $queryBuilder
            ->leftJoin(sprintf('%s.intervenant', $alias), $intervenantAlias)
            ->leftJoin(sprintf('%s.beneficiaires', $alias), $beneficiairesAlias)
            ->join('App\Entity\Utilisateur', $utilisateurAlias,
                Join::WITH, sprintf('(%s = %s.utilisateur or %s = %s.utilisateur) and %s.uid = :uid',
                    $utilisateurAlias, $intervenantAlias, $utilisateurAlias, $beneficiairesAlias, $utilisateurAlias
                )
            )
            ->setParameter('uid', $utilisateur->uid);
    }

    public function getDescription(string $resourceClass): array
    {
        //Pas disponible coté user, filtre ajouté de force par le provider
        //todo: le proposer coté appel externe type mescalendriers?
        return [];
    }


}