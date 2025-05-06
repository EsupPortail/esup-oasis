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

use App\Entity\MembreCommission;
use App\Message\ModificationUtilisateurMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @extends ServiceEntityRepository<MembreCommission>
 *
 * @method MembreCommission|null find($id, $lockMode = null, $lockVersion = null)
 * @method MembreCommission|null findOneBy(array $criteria, array $orderBy = null)
 * @method MembreCommission[]    findAll()
 * @method MembreCommission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MembreCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly MessageBusInterface $messageBus)
    {
        parent::__construct($registry, MembreCommission::class);
    }

    public function save(MembreCommission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
        $this->messageBus->dispatch(new ModificationUtilisateurMessage($entity->getUtilisateur()));
    }

    public function remove(MembreCommission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
        $this->messageBus->dispatch(new ModificationUtilisateurMessage($entity->getUtilisateur()));
    }
}
