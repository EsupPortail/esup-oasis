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

use App\Entity\AvisEse;
use App\Message\ModificationUtilisateurMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @extends ServiceEntityRepository<AvisEse>
 *
 * @method AvisEse|null find($id, $lockMode = null, $lockVersion = null)
 * @method AvisEse|null findOneBy(array $criteria, array $orderBy = null)
 * @method AvisEse[]    findAll()
 * @method AvisEse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvisEseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly MessageBusInterface $messageBus)
    {
        parent::__construct($registry, AvisEse::class);
    }

    public function save(AvisEse $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
        $this->messageBus->dispatch(new ModificationUtilisateurMessage($entity->getUtilisateur()));
    }

    public function remove(AvisEse $entity, bool $flush = false): void
    {
        $this->messageBus->dispatch(new ModificationUtilisateurMessage($entity->getUtilisateur()));

        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
