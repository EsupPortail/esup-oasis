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

use App\Entity\ModificationEtatDemande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModificationEtatDemande>
 *
 * @method ModificationEtatDemande|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModificationEtatDemande|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModificationEtatDemande[]    findAll()
 * @method ModificationEtatDemande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModificationEtatDemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModificationEtatDemande::class);
    }

    public function save(ModificationEtatDemande $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
