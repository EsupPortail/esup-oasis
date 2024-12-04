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

use ApiPlatform\Doctrine\Common\Filter\OrderFilterTrait;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class InterventionForfaitNomIntervenantOrderFilter extends AbstractFilter
{
    use OrderFilterTrait;

    public function __construct(ManagerRegistry          $managerRegistry, string $orderParameterName = 'order',
                                ?LoggerInterface         $logger = null,
                                ?array                   $properties = null,
                                ?NameConverterInterface  $nameConverter = null,
                                private readonly ?string $orderNullsComparison = null)
    {
        $this->orderParameterName = $orderParameterName;
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }


    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if ($property !== $this->orderParameterName) {
            return;
        }

        $supportedProperties = array_filter(array_keys($value), fn($prop) => $prop === 'intervenant.utilisateur.nom');
        if (empty($supportedProperties)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $intervenantAlias = $queryNameGenerator->generateJoinAlias('intervenant');
        $utilisateurAlias = $queryNameGenerator->generateJoinAlias('utilisateur');
        $fieldAlias = $queryNameGenerator->generateParameterName('order');
        $hiddenSelect = sprintf('upper(unaccent(%s.nom))', $utilisateurAlias);

        $queryBuilder->join(sprintf('%s.intervenant', $alias), $intervenantAlias);
        $queryBuilder->join(sprintf('%s.utilisateur', $intervenantAlias), $utilisateurAlias);
        $queryBuilder->addSelect($hiddenSelect . ' as hidden ' . $fieldAlias);
        $queryBuilder->addOrderBy($fieldAlias, $value['intervenant.utilisateur.nom']);
    }
}