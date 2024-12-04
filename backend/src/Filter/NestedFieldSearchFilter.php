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
use ApiPlatform\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use App\ApiResource\Utilisateur;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class NestedFieldSearchFilter extends AbstractFilter
{

    use PropertyHelperTrait;

    protected array $joins = [];

    public function __construct(ManagerRegistry                          $managerRegistry,
                                protected readonly IriConverterInterface $iriConverter,
                                ?LoggerInterface                         $logger = null,
                                ?array                                   $properties = null,
                                ?NameConverterInterface                  $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    public function getJoinAlias($path)
    {
        if (!array_key_exists($path, $this->joins)) {
            throw new Exception('chemin de jointure ' . $path . ' non présent');
        }
        return $this->joins[$path];
    }

    #[Override] public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->getProperties() as $property => $value) {
            $description[$property] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => false,
                'openapi' => [
                    'description' => $value['desc'],
                    'name' => $property,
                    'type' => 'string',
                ],
            ];
            $description[$property . '[]'] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => true,
                'openapi' => [
                    'description' => $value['desc'],
                    'name' => $property,
                    'type' => 'string',
                ],
            ];
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

        $alias = $queryBuilder->getRootAliases()[0];

        $targetField = $this->getProperties()[$property]['mapping'];

        //on ajoute les jointures si besoin
        $currentResourceClass = $resourceClass;
        $prefix = '';

        while ($this->isPropertyNested($targetField, $currentResourceClass)) {
            $metadata = $this->getClassMetadata($currentResourceClass);
            $parts = explode('.', $targetField);
            $current = array_shift($parts);

            $nextAlias = $queryNameGenerator->generateJoinAlias($current);
            $queryBuilder->join(sprintf('%s.%s', $alias, $current), $nextAlias);

            $prefix = match ($prefix) {
                '' => $current,
                default => $prefix . '.' . $current
            };
            $this->joins[$prefix] = $nextAlias;
            $alias = $nextAlias;
            $targetField = implode('.', $parts);
            $currentResourceClass = $metadata->getAssociationTargetClass($current);
        }

        //la définition du filtre était ok?
        if (str_contains($targetField, '.')) {
            //on sort comme un sagouin, erreur du dev.
            throw new Exception('Filtre mal défini, mapping incorrect : ' . $this->getProperties()[$property]['mapping']);
        }

        $this->doFilter($alias, $currentResourceClass, $targetField, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }

    /**
     * Implémentation par défaut - on teste l'égalité
     *
     * @param string $alias
     * @param string $targetField
     * @param array $value
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param Operation|null $operation
     * @param array $context
     * @return void
     */
    protected function doFilter(string $alias, string $currentResourceClass, string $targetField, array $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator,
                                string $resourceClass, ?Operation $operation, array $context): void
    {
        $exprs = [];
        foreach ($value as $val) {
            try {
                $val = $this->iriConverter->getResourceFromIri($val);
                if ($val instanceof Utilisateur) {
                    $additionalJoinAlias = $queryNameGenerator->generateJoinAlias('utilisateur');
                    $uidParam = $queryNameGenerator->generateParameterName('uid');
                    $queryBuilder->join(sprintf('%s.%s', $alias, $targetField), $additionalJoinAlias);
                    $exprs[] = $queryBuilder->expr()->eq(':' . $uidParam, $additionalJoinAlias . '.uid');
                    $queryBuilder->setParameter($uidParam, $val->uid);
                } else {
                    $exprs[] = $queryBuilder->expr()->eq($val->id, $alias . '.' . $targetField);
                }
            } catch (Exception $e) {
                $exprs[] = $queryBuilder->expr()->eq($alias . '.' . $targetField, $val);
            }
        }

        $orX = $queryBuilder->expr()->orX();
        $orX->addMultiple($exprs);

        $queryBuilder->andWhere($orX);
    }


}