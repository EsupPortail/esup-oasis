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
use App\ApiResource\ProfilBeneficiaire;
use App\Entity\Evenement;
use App\Entity\InterventionForfait;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Contracts\Service\Attribute\Required;

class BilanFinancierProfilBeneficiairesFilter extends AbstractFilter
{

    protected IriConverterInterface $iriConverter;

    #[Required]
    public function setIriConverter(IriConverterInterface $converter): void
    {
        $this->iriConverter = $converter;
    }

    /**
     * @inheritDoc
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator,
                                      string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== 'profil' || !in_array($resourceClass, [Evenement::class, InterventionForfait::class])) {
            return;
        }
        if (!is_array($value)) {
            $value = [$value];
        }

        /**
         * @var ProfilBeneficiaire[] $profils
         */
        $profils = array_map(fn($val) => $this->iriConverter->getResourceFromIri($val), $value);

        $alias = $queryBuilder->getRootAliases()[0];
        $benefAlias = $queryNameGenerator->generateJoinAlias('beneficiaires');
        $profilAlias = $queryNameGenerator->generateJoinAlias('profil');
        $profilIdsParam = $queryNameGenerator->generateParameterName('profilIds');

        $queryBuilder->join(sprintf('%s.beneficiaires', $alias), $benefAlias)
            ->join(sprintf('%s.profil', $benefAlias), $profilAlias)
            ->andWhere(sprintf('%s.id in (:%s)', $profilAlias, $profilIdsParam))
            ->setParameter($profilIdsParam, array_map(fn($profil) => $profil->id, $profils), ArrayParameterType::INTEGER);

    }

    /**
     * @inheritDoc
     */
    public function getDescription(string $resourceClass): array
    {
        $property = 'profil';
        $description[$property] = [
            'property' => $property,
            'type' => Type::BUILTIN_TYPE_STRING,
            'required' => false,
            'is_collection' => false,
            'openapi' => new Parameter(
                name: $property,
                in: 'query',
                description: 'IRI profil',
            ),
        ];
        $description[$property . '[]'] = [
            'property' => $property,
            'type' => Type::BUILTIN_TYPE_STRING,
            'required' => false,
            'is_collection' => true,
            'openapi' => new Parameter(
                name: $property,
                in: 'query',
                description: 'IRIs profil',
            ),
        ];
        return $description;
    }
}