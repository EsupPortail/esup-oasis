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

use App\Entity\Demande;
use App\Entity\Question;
use App\Entity\Reponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reponse>
 *
 * @method Reponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reponse[]    findAll()
 * @method Reponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reponse::class);
    }

    public function save(Reponse $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retourne les réponses à des questions avec champCible pour cette demande
     *
     * @param Demande $demande
     * @return Reponse[]
     */
    public function getReponsesARecuperer(Demande $demande): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.question', 'q')
            ->andWhere("q.champCible is not null or q.typeReponse = :type_fichier")
            ->andWhere('r.repondant = :demandeur')
            ->andWhere('r.campagne = :campagne')
            ->setParameter('demandeur', $demande->getDemandeur())
            ->setParameter('campagne', $demande->getCampagne())
            ->setParameter('type_fichier', Question::TYPE_FILE);

        return $qb->getQuery()->getResult();
    }
}
