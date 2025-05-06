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
use ApiPlatform\OpenApi\Model\Parameter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Contracts\Service\ResetInterface;

class UtilisateurAmenagementEnCoursFilterHelper implements ResetInterface
{
    use ClockAwareTrait;

    private array $joins = [];

    public function __construct(private readonly IriConverterInterface $iriConverter)
    {
    }

    public function getResourceIdFromIri(string $iri): int
    {
        $resource = $this->iriConverter->getResourceFromIri($iri);
        return $resource->id;
    }

    /**
     * @param $value
     * @return array|float[]|int[]|string[]
     */
    public function getIds($value): array
    {
        return array_map(
            fn($val) => match (true) {
                is_numeric($val) => $val,
                default => $this->getResourceIdFromIri($val)
            },
            match (true) {
                is_array($value) => $value,
                default => [$value]
            }
        );
    }

    public function getDescription(string $property): array
    {
        return [
            $property => [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => false,
                'openapi' => new Parameter(
                    name: $property,
                    in: 'query',
                    description: $property,
                ),
            ],
            $property . '[]' => [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => true,
                'openapi' => new Parameter(
                    name: $property,
                    in: 'query',
                    description: $property,
                ),
            ],

        ];
    }

    public function ajouterJointuresCategories(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, array|string $categorieIds): string
    {

        if (!array_key_exists('categorie', $this->joins)) {
            $categorieIds = $this->getIds($categorieIds);
            $typeAlias = $this->ajouterJointuresType($queryBuilder, $queryNameGenerator);

            $categorieAlias = $queryNameGenerator->generateJoinAlias('categorie');
            $categorieIdsParameter = $queryNameGenerator->generateParameterName('categorieIds');

            $queryBuilder->join(sprintf('%s.categorie', $typeAlias), $categorieAlias)
                ->andWhere($queryBuilder->expr()->in(sprintf('%s.id', $categorieAlias), ':' . $categorieIdsParameter))
                ->setParameter($categorieIdsParameter, $categorieIds);

            $this->joins['categorie'] = $categorieAlias;
        }
        return $this->joins['categorie'];
    }

    private function ajouterJointuresAmenagement(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): string
    {
        if (!array_key_exists('amenagements', $this->joins)) {
            $utilisateurAlias = $queryBuilder->getRootAliases()[0];
            $beneficiairesAlias = $queryNameGenerator->generateJoinAlias('beneficiaires');
            $amenagementsAlias = $queryNameGenerator->generateJoinAlias('amenagements');
            $nowParam = $queryNameGenerator->generateParameterName('now');

            $queryBuilder->join(sprintf('%s.beneficiaires', $utilisateurAlias), $beneficiairesAlias)
                ->join(sprintf('%s.amenagements', $beneficiairesAlias), $amenagementsAlias)
                ->andWhere(sprintf('%1$s.debut <= :%2$s and (%1$s.fin is null or %1$s.fin > :%2$s)', $amenagementsAlias, $nowParam))
                ->setParameter($nowParam, $this->now());

            $this->joins['amenagements'] = $amenagementsAlias;
        }

        return $this->joins['amenagements'];
    }

    public function ajouterJointuresType(QueryBuilder      $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator,
                                         array|string|null $types = null): string
    {
        if (!array_key_exists('type', $this->joins)) {
            $amenagementsAlias = $this->ajouterJointuresAmenagement($queryBuilder, $queryNameGenerator);
            $typeAlias = $queryNameGenerator->generateJoinAlias('type');

            $queryBuilder->join(sprintf('%s.type', $amenagementsAlias), $typeAlias);
            $this->joins['type'] = $typeAlias;
        }

        if (null !== $types) {
            $typeIds = $this->getIds($types);

            $typeIdsParam = $queryNameGenerator->generateParameterName('typeIds');
            $queryBuilder->andWhere($queryBuilder->expr()->in(sprintf('%s.id', $this->joins['type']), ':' . $typeIdsParam))
                ->setParameter($typeIdsParam, $typeIds);
        }

        return $this->joins['type'];
    }

    public function ajouterJointuresDomaine(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, $value, $property): void
    {
        $typeAlias = $this->ajouterJointuresType($queryBuilder, $queryNameGenerator);
        $domaineParam = $queryNameGenerator->generateParameterName('domaine');

        $queryBuilder->andWhere(sprintf('%s.%s = :%s', $typeAlias, $property, $domaineParam))
            ->setParameter($domaineParam, $value == 'true');
    }

    public function reset(): void
    {
        $this->joins = [];
    }
}