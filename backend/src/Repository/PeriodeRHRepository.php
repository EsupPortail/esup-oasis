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

use App\Entity\PeriodeRH;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\ClockAwareTrait;

/**
 * @extends ServiceEntityRepository<PeriodeRH>
 *
 * @method PeriodeRH|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeriodeRH|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeriodeRH[]    findAll()
 * @method PeriodeRH[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeriodeRHRepository extends ServiceEntityRepository
{
    use ClockAwareTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeriodeRH::class);
    }

    public function save(PeriodeRH $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PeriodeRH $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return PeriodeRH[]
     */
    public function locked(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.dateButoir <= :now')
            ->setParameter('now', $this->now());

        return $qb->getQuery()->getResult();
    }

    /**
     * @param DateTimeInterface $debut
     * @return PeriodeRH|null
     * @throws NonUniqueResultException
     */
    public function findPeriodePourDate(DateTimeInterface $debut): ?PeriodeRH
    {
        $debut = new DateTime($debut->format('Y-m-d'));

        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.debut <= :debut')
            ->andWhere('p.fin >= :debut')
            ->setParameter('debut', $debut);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param DateTimeInterface $debut
     * @return bool
     */
    public function periodeExisteApres(DateTimeInterface $debut): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.debut > :debut')
            ->setParameter('debut', $debut);

        return !empty($qb->getQuery()->getResult());
    }

    /**
     * @param DateTimeInterface|null $debut
     * @param DateTimeInterface|null $fin
     * @return PeriodeRH[]
     */
    public function chevauchements(?DateTimeInterface $debut, ?DateTimeInterface $fin): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.debut between :debut and :fin')
            ->orWhere(':debut between p.debut and p.fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin);

        return $qb->getQuery()->getResult();
    }
}
