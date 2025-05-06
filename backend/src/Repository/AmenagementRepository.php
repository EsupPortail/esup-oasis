<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */

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

use App\Entity\Amenagement;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Amenagement>
 *
 * @method Amenagement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Amenagement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Amenagement[]    findAll()
 * @method Amenagement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AmenagementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Amenagement::class);
    }

    public function save(Amenagement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Amenagement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param DateTimeInterface $now
     * @return Amenagement[]
     */
    public function findEnCours(DateTimeInterface $now): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.debut <= :now')
            ->andWhere('a.fin is null or a.fin > :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
