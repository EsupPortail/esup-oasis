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
use App\Entity\Evenement;
use App\Entity\InterventionForfait;
use App\Entity\PeriodeRH;
use App\State\PeriodeRH\PeriodeManager;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Service\Attribute\Required;

class PeriodeDansIntervalleFilter extends AbstractFilter
{

    private PeriodeManager $periodeManager;

    #[Required]
    public function setPeriodeManager(PeriodeManager $manager): void
    {
        $this->periodeManager = $manager;
    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator,
                                      string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!in_array($property, array_keys($this->properties)) || !in_array($resourceClass, [Evenement::class, InterventionForfait::class])) {
            return;
        }
        //on attend un tableau ['debut'=>DateTime, 'fin'=>DateTime]
        if (!is_array($value) || !array_key_exists('debut', $value) || !array_key_exists('fin', $value)) {
            return;
        }

        $debut = $value['debut'];
        $fin = $value['fin'];


        $periodesConcernees = $this->periodeManager->periodesDansIntervalle($debut, $fin, versionFinanciere: true);

        //On filtre des Evenement ou des InterventionForfait
        $alias = $queryBuilder->getRootAliases()[0];
        $fieldname = $resourceClass === Evenement::class ? 'periodePriseEnCompteRH' : 'periode';
        $periodeAlias = $queryNameGenerator->generateJoinAlias('periode');
        $periodeIdsParameter = $queryNameGenerator->generateParameterName('periodeIds');

        $queryBuilder->join(sprintf('%s.%s', $alias, $fieldname), $periodeAlias)
            ->andWhere(sprintf('%s.id in (:%s)', $periodeAlias, $periodeIdsParameter))
            ->setParameter(
                key: $periodeIdsParameter,
                value: array_map(fn(PeriodeRH $periode) => $periode->getId(), $periodesConcernees),
                type: ArrayParameterType::INTEGER
            );

    }


    public function getDescription(string $resourceClass): array
    {
        //hidden
        return [];
    }
}