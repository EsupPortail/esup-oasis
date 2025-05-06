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

use App\Entity\SportifHautNiveau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SportifHautNiveau>
 *
 * @method SportifHautNiveau|null find($id, $lockMode = null, $lockVersion = null)
 * @method SportifHautNiveau|null findOneBy(array $criteria, array $orderBy = null)
 * @method SportifHautNiveau[]    findAll()
 * @method SportifHautNiveau[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SportifHautNiveauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SportifHautNiveau::class);
    }

    public function truncate(): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $truncateSql = $connection->getDatabasePlatform()->getTruncateTableSQL('sportif_haut_niveau');
        $connection->executeQuery($truncateSql);
    }

    public function save(SportifHautNiveau $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SportifHautNiveau $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
