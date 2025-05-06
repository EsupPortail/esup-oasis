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

use App\Entity\TypeEvenement;
use App\Entity\Utilisateur;
use App\Message\ModificationUtilisateurMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 *
 * @method Utilisateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Utilisateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Utilisateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    use ClockAwareTrait;

    public function __construct(ManagerRegistry $registry, private readonly MessageBusInterface $messageBus)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function save(Utilisateur $entity, bool $flush = false): void
    {
        //on force le recalcul des rôles stockés
        $entity->setRoles($entity->getRolesCalcules());

        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        $this->messageBus->dispatch(new ModificationUtilisateurMessage($entity));
    }

    public function remove(Utilisateur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        $this->messageBus->dispatch(new ModificationUtilisateurMessage($entity));
    }

    public function search(string $term)
    {
        $searchTerm = '%' . $term . '%';
        $qb = $this->createQueryBuilder('u', 'u.uid')
            ->andWhere('u.uid like :term or u.nom like :term or u.prenom like :term')
            ->setParameter('term', $searchTerm)
            ->orderBy('lower(u.nom), lower(u.prenom)');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return Utilisateur[]
     */
    public function findAll(): array
    {
        $qb = $this->createQueryBuilder('u', 'u.uid')
            ->orderBy('lower(u.nom), lower(u.prenom)');
        return $qb->getQuery()
            ->getResult();
    }

    public function findIntervenantsActifs()
    {
        return $this->createQueryBuilder('u')
            ->join('u.intervenant', 'i')
            ->join('i.typesEvenements', 'typesEvenements')
            ->andWhere('typesEvenements.id != :typeRenfort')
            ->andWhere('i.debut <= :now and (i.fin is null or i.fin > :now)')
            ->setParameter('now', $this->now())
            ->setParameter('typeRenfort', TypeEvenement::TYPE_RENFORT)
            ->getQuery()->getResult();
    }

    public function countIntervenantsActifs()
    {
        return ($this->createQueryBuilder('u')
            ->select('count(u.id) as nb')
            ->join('u.intervenant', 'i')
            ->join('i.typesEvenements', 'typesEvenements')
            ->andWhere('typesEvenements.id != :typeRenfort')
            ->andWhere('i.debut <= :now and (i.fin is null or i.fin > :now)')
            ->setParameter('now', $this->now())
            ->setParameter('typeRenfort', TypeEvenement::TYPE_RENFORT)
            ->getQuery()->getOneOrNullResult())['nb'];
    }
}
