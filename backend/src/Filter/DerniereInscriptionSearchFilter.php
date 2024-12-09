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

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DerniereInscriptionSearchFilter extends NestedFieldSearchFilter
{
    private array $decorated;

    public function __construct(ManagerRegistry         $managerRegistry,
                                IriConverterInterface   $iriConverter,
                                ?LoggerInterface        $logger = null,
                                ?array                  $properties = null,
                                ?NameConverterInterface $nameConverter = null)
    {
        foreach ($properties as $name => $property) {
            $type = $property['type'];
            $this->decorated[$name] = match ($type) {
                'string' => new CaseInsensitiveSearchFilter($managerRegistry, $iriConverter, $logger, [$name => $property], $nameConverter),
                default => new NestedFieldSearchFilter($managerRegistry, $iriConverter, $logger, [$name => $property], $nameConverter),
            };
        }
        parent::__construct($managerRegistry, $iriConverter, $logger, $properties, $nameConverter);
    }

    #[Override]
    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->decorated as $decorated) {
            foreach ($decorated->getDescription($resourceClass) as $key => $desc) {
                $description[$key] = $desc;
            }
        }
        return $description;
    }

    #[Override]
    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if (!in_array($property, array_keys($this->getProperties()))) {
            return;
        }
        if (!is_array($value)) {
            $value = [$value];
        }
        if (empty($value)) {
            return;
        }

        $this->decorated[$property]->filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);

        $pathEtudiant = $this->getProperties()[$property]['etudiant'] ?? null;
        if (null !== $pathEtudiant) {
            $pathInscriptions = $pathEtudiant . '.inscriptions';
            $aliasEtudiant = $this->decorated[$property]->getJoinAlias($pathEtudiant);
        } else {
            //on est directement sur utilisateur
            $pathInscriptions = 'inscriptions';
            $aliasEtudiant = $queryBuilder->getRootAliases()[0];
        }
        $aliasInscriptions = $this->decorated[$property]->getJoinAlias($pathInscriptions);
        $aliasInscriptionsPlusRecentes = $queryNameGenerator->generateJoinAlias('inscriptions');

        $queryBuilder->leftJoin(sprintf('%s.inscriptions', $aliasEtudiant), $aliasInscriptionsPlusRecentes,
            Join::WITH, sprintf('%s.debut > %s.debut', $aliasInscriptionsPlusRecentes, $aliasInscriptions))
            ->andWhere(sprintf('%s.id is null', $aliasInscriptionsPlusRecentes));
    }

}