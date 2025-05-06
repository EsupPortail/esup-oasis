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
use ApiPlatform\OpenApi\Model\Parameter;
use App\Entity\TypeEvenement;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class EvenementsAValiderFilter extends AbstractFilter
{

    public function __construct(ManagerRegistry         $managerRegistry, private readonly Security $security,
                                ?LoggerInterface        $logger = null, ?array $properties = null,
                                ?NameConverterInterface $nameConverter = null,)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'aValider' => [
                'property' => 'aValider',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'openapi' => new Parameter(
                    name: 'aValider',
                    in: 'query',
                    description: 'uniquement les événements à valider ?',
                ),
            ],
        ];
    }

    protected function filterProperty(string                      $property, $value, QueryBuilder $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass,
                                      ?Operation                  $operation = null, array $context = []): void
    {
        if ($property !== 'aValider') {
            return;
        }

        $user = $this->security->getUser();
        $alias = $queryBuilder->getRootAliases()[0];
        $typeAlias = $queryNameGenerator->generateJoinAlias('type');


        $queryBuilder->join($alias . '.type', $typeAlias, Join::WITH, $typeAlias . '.id=:typeRenfort')
            ->andWhere($alias . ('.dateValidation is null'))
            ->setParameter('typeRenfort', TypeEvenement::TYPE_RENFORT);
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            //il faut regarder les événements des renforts qui sont sur le même service que l'utilisateur connecté
            $renfortAlias = $queryNameGenerator->generateJoinAlias('utilisateurCreation');
            $renfortServiceAlias = $queryNameGenerator->generateJoinAlias('services');

            $queryBuilder->join($alias . '.utilisateurCreation', $renfortAlias)
                ->join($renfortAlias . '.services', $renfortServiceAlias, Join::WITH,
                    ':user MEMBER OF ' . $renfortServiceAlias . '.utilisateurs')
                ->setParameter('user', $user);
        }
    }
}