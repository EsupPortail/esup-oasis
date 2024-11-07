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

namespace App\Repository;

use App\Entity\TypeSuiviAmenagement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeSuiviAmenagement>
 *
 * @method TypeSuiviAmenagement|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeSuiviAmenagement|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeSuiviAmenagement[]    findAll()
 * @method TypeSuiviAmenagement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeSuiviAmenagementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeSuiviAmenagement::class);
    }

    public function save(TypeSuiviAmenagement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

    }

}
