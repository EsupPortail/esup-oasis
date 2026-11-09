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

namespace App\Repository;

use App\Entity\DecisionAmenagementExamens;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DecisionAmenagementExamens>
 *
 * @method DecisionAmenagementExamens|null find($id, $lockMode = null, $lockVersion = null)
 * @method DecisionAmenagementExamens|null findOneBy(array $criteria, array $orderBy = null)
 * @method DecisionAmenagementExamens[]    findAll()
 * @method DecisionAmenagementExamens[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DecisionAmenagementExamensRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DecisionAmenagementExamens::class);
    }

    public function save(DecisionAmenagementExamens $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DecisionAmenagementExamens $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Décisions en cours de signature, les moins récemment vérifiées d'abord :
     * d'un lot à l'autre, aucune n'est ignorée indéfiniment.
     *
     * @return DecisionAmenagementExamens[]
     */
    public function enCoursDeSignature(int $limite): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.etat = :enSignature')
            ->andWhere('d.idDocumentParapheur IS NOT NULL')
            ->setParameter('enSignature', DecisionAmenagementExamens::ETAT_EN_SIGNATURE)
            ->addOrderBy('CASE WHEN d.derniereVerificationSignature IS NULL THEN 0 ELSE 1 END', 'ASC')
            ->addOrderBy('d.derniereVerificationSignature', 'ASC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
}
