<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
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
use ApiPlatform\OpenApi\Model\Parameter;
use App\Entity\DecisionAmenagementExamens;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\TypeInfo\TypeIdentifier;

class EtatDecisionAmenagementFilter extends AbstractFilter
{
    public const string PROPERTY = 'etatDecisionAmenagement';

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $etats = [
            DecisionAmenagementExamens::ETAT_EDITE,
            DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE,
            DecisionAmenagementExamens::ETAT_VALIDE,
            DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS,
            DecisionAmenagementExamens::ETAT_EN_SIGNATURE,
            DecisionAmenagementExamens::ETAT_REFUSEE,
        ];

        if ($property !== self::PROPERTY) {
            return;
        }

        if (!is_string($value) || !in_array($value, $etats)) {
            $queryBuilder->andWhere('1=2');
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $etatParam = $queryNameGenerator->generateParameterName('etat');

        $em = $queryBuilder->getEntityManager();

        // MAX(debut) pour CE bénéficiaire (tous états)
        $maxQb = $em->createQueryBuilder();
        $maxQb
            ->select('MAX(decMax.debut)')
            ->from(DecisionAmenagementExamens::class, 'decMax')
            ->andWhere(sprintf('decMax.beneficiaire = %s', $rootAlias));

        // EXISTS de la décision qui est à ce MAX(debut) et avec l'état demandé
        $existsQb = $em->createQueryBuilder();
        $existsQb
            ->select('1')
            ->from(DecisionAmenagementExamens::class, 'decLast')
            ->andWhere(sprintf('decLast.beneficiaire = %s', $rootAlias))
            ->andWhere(sprintf('decLast.debut = (%s)', $maxQb->getDQL()))
            ->andWhere(sprintf('decLast.etat = :%s', $etatParam));

        $queryBuilder->andWhere($queryBuilder->expr()->exists($existsQb->getDQL()))->setParameter($etatParam, $value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::PROPERTY => [
                'property' => self::PROPERTY,
                'type' => TypeIdentifier::STRING->value,
                'required' => false,
                'openapi' => new Parameter(name: self::PROPERTY, in: 'query', description: 'Etat Décision', schema: [
                    'type' => 'string',
                ]),
            ],
        ];
    }
}
