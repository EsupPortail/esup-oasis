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

use App\Entity\Fichier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fichier>
 *
 * @method Fichier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fichier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fichier[]    findAll()
 * @method Fichier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FichierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fichier::class);
    }

    public function save(Fichier $entity, $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Fichier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Fichier[]
     */
    public function fichiersObsoletes(): array
    {
        $qb = $this->createQueryBuilder('fichier')
            ->leftJoin('fichier.avisEse', 'avis')
            ->leftJoin('fichier.reponses', 'reponses')
            ->leftJoin('fichier.entretien', 'entretien')
            ->leftJoin('fichier.pieceJointeBeneficiaire', 'pj')
            ->leftJoin('fichier.decisionAmenagementExamens', 'decision')
            ->leftJoin('fichier.valeurParametres', 'valeurParametres')
            ->leftJoin('fichier.bilan', 'bilan')
            ->andWhere('avis.id is null')
            ->andWhere('reponses.id is null')
            ->andWhere('entretien.id is null')
            ->andWhere('pj.id is null')
            ->andWhere('valeurParametres.id is null')
            ->andWhere('bilan.id is null')
            ->andWhere('decision.id is null');

        return $qb->getQuery()->getResult();
    }

}
