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

use App\ApiResource\Utilisateur;
use App\Entity\Evenement;
use App\Entity\TypeEvenement;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 *
 * @method Evenement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evenement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evenement[]    findAll()
 * @method Evenement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    public function save(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllNotLocked(?Utilisateur $utilisateur = null): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.periodePriseEnCompteRH is null')
            ->getQuery()
            ->getResult();
    }

    public function findAllLocked(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.periodePriseEnCompteRH is not null')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param DateTimeInterface|null $finPeriode
     * @return Evenement[]
     */
    public function findAllNotLockedBefore(?DateTimeInterface $finPeriode): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.periodePriseEnCompteRH is null')
            ->andWhere('e.debut < :finPeriode')
            ->andWhere('e.fin < :finPeriode')
            ->setParameter('finPeriode', $finPeriode)
            ->getQuery()
            ->getResult();
    }

    public function countByDateInterval(DateTimeInterface $debut, ?DateTimeInterface $fin = null, $nonAffectes = false,
                                        ?Utilisateur      $utilisateur = null, $avecRenforts = false, $sansBeneficiaire = false)
    {
        if (null === $fin) {
            $fin = (new DateTime($debut->format('Y-m-d')))->modify('+10 years');
        }
        $qb = $this->createQueryBuilder('e')
            ->select('count(distinct e.id) as nb')
            ->where('e.debut between :debutJournee and :finJournee and e.dateAnnulation is null')
            ->setParameter('debutJournee', $debut->format('Y-m-d 00:00'))
            ->setParameter('finJournee', $fin->format('Y-m-d 23:59'));

        if ($nonAffectes) {
            $qb->andWhere('e.intervenant is null');
        }
        if (!$avecRenforts) {
            $qb->join('e.type', 'type', JOIN::WITH, 'type.id != :typeId')
                ->setParameter('typeId', TypeEvenement::TYPE_RENFORT);
        }
        if ($sansBeneficiaire) {
            $qb->andWhere('e.beneficiaires is empty')
                ->andWhere('type.forfait = false');
        }

        $this->addUtilisateurFilter($utilisateur, $qb);

        return ($qb->getQuery()->getOneOrNullResult())['nb'];
    }

    /**
     * @param Utilisateur|null $utilisateur
     * @return Evenement[]
     */
    public function findValidables(?Utilisateur $utilisateur = null): array
    {

        $qb = $this->createQueryBuilder('e')
            ->join('e.type', 'type')
            ->where('e.dateValidation is null and type.avecValidation = true and e.dateAnnulation is null');

        //On est sur les interventions des renforts, validation par les CAS du même service / les admins
        if (null !== $utilisateur && !in_array(\App\Entity\Utilisateur::ROLE_ADMIN, $utilisateur->roles)) {
            $qb->join('e.utilisateurCreation', 'renfort')
                ->join('renfort.services', 'srv')
                ->join('srv.utilisateurs', 'u', Join::WITH, 'u.uid = :uid')
                ->setParameter('uid', $utilisateur->uid);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Utilisateur|null $utilisateur
     * @param QueryBuilder $qb
     * @return void
     */
    protected function addUtilisateurFilter(?Utilisateur $utilisateur, QueryBuilder $qb): void
    {
        if (null !== $utilisateur && !in_array(\App\Entity\Utilisateur::ROLE_ADMIN, $utilisateur->roles)) {
            //where evenement.beneficiaire.gestionnaire.service = utilisateur.service or evenement.intervenant.utilisateur = utilisateur!
            $qb->join('e.beneficiaires', 'b')
                ->join('b.gestionnaire', 'g')
                ->join('g.services', 's') //null par défaut pour les admins - voir s'il faut changer la logique un jour...
                ->join('s.utilisateurs', 'u')
                ->leftJoin('e.intervenant', 'intervenant') //intervenant est nullable
                ->leftJoin('intervenant.utilisateur', 'u2')
                ->andWhere('u.uid = :uid or u2.uid = :uid')
                ->setParameter('uid', $utilisateur->uid);
        }
    }

    /**
     * @param DateTimeImmutable $depuis
     * @param DateTimeImmutable $jusqua
     * @param bool $sansIntervenants
     * @return Evenement[]
     */
    public function evenementsAVenir(DateTimeImmutable $depuis, DateTimeImmutable $jusqua, bool $sansIntervenants): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.dateAnnulation is null')
            ->andWhere('e.debut < :fin')
            ->andWhere('e.debut > :debut')
            ->setParameter('fin', $jusqua)
            ->setParameter('debut', $depuis)
            ->addOrderBy('e.debut');

        if (!$sansIntervenants) {
            $qb->andWhere('e.intervenant is not null');

        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Utilisateur $utilisateur
     * @param             $debutIntervalle
     * @param             $finIntervalle
     * @return Evenement[]
     */
    public function occupationsUtilisateur(Utilisateur $utilisateur, $debutIntervalle, $finIntervalle): array
    {
        $where = "(:debut between date_add(e.debut, (0 - e.tempsPreparation), 'MINUTE') and date_add(e.fin, e.tempsSupplementaire, 'MINUTE')";
        $where .= " or e.debut between :debut and :fin)";

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.beneficiaires', 'benef')
            ->leftJoin('e.intervenant', 'i')
            ->join(join: 'App\Entity\Utilisateur', alias: 'u', conditionType: Join::WITH,
                condition: 'u.uid = :uid and (u = benef.utilisateur or u = i.utilisateur)')
            ->setParameter('uid', $utilisateur->uid)
            ->andWhere($where)
            ->setParameter('debut', $debutIntervalle)
            ->setParameter('fin', $finIntervalle);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param DateTimeInterface $now
     * @param ?Utilisateur $utilisateur
     * @param bool $avecRenforts
     * @return Evenement[]
     */
    public function evenementsDuJour(DateTimeInterface $now, ?Utilisateur $utilisateur, $avecRenforts = false): array
    {
        return $this->evenementsIntervalle($now, $now, $utilisateur, $avecRenforts);
    }

    /**
     * @param DateTimeInterface $debutSemaine
     * @param DateTimeInterface $finSemaine
     * @param Utilisateur|null $utilisateur
     * @param bool $avecRenforts
     * @return array
     */
    public function evenementsIntervalle(DateTimeInterface $debutSemaine, DateTimeInterface $finSemaine,
                                         ?Utilisateur      $utilisateur, $avecRenforts = false): array
    {
        $qb = $this->createQueryBuilder('e')
            ->distinct('e.id')
            ->andWhere("e.debut between :debutJournee and :finJournee and e.dateAnnulation is null")
            ->setParameter('debutJournee', $debutSemaine->format('Y-m-d 00:00'))
            ->setParameter('finJournee', $finSemaine->format('Y-m-d 23:59'));

        $this->addUtilisateurFilter($utilisateur, $qb);

        if (!$avecRenforts) {
            $qb->join('e.type', 'type', JOIN::WITH, 'type.id != :typeId')
                ->setParameter('typeId', TypeEvenement::TYPE_RENFORT);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByIdBeneficiaire(?int $id)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('count(e.id) as nb')
            ->join('e.beneficiaires', 'b')
            ->andWhere('b.id = :id')
            ->andWhere('e.dateAnnulation is null')
            ->setParameter('id', $id);

        return ($qb->getQuery()->getOneOrNullResult())['nb'];
    }

}
