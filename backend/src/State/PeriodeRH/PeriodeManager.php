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

namespace App\State\PeriodeRH;

use App\ApiResource\PeriodeRH;
use App\Entity\PeriodeRH as PeriodeRHEntity;
use App\Repository\EvenementRepository;
use App\Repository\PeriodeRHRepository;
use DateTimeInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;

final readonly class PeriodeManager
{
    use ClockAwareTrait;

    public function __construct(private PeriodeRHRepository $periodeRepository,
                                private EvenementRepository $evenementRepository,
                                private Security            $security)
    {

    }

    /**
     * @param PeriodeRH $resource
     * @return void
     */
    public function save(PeriodeRH $resource): PeriodeRHEntity
    {
        if (null === $resource->id) {
            $entity = new PeriodeRHEntity();
        } else {
            $entity = $this->periodeRepository->find($resource->id);
        }

        $entity->setDebut($resource->debut);
        $entity->setFin($resource->fin);
        $entity->setButoir($resource->butoir);
        if ($resource->envoyee && null === $entity->getDateEnvoi()) {
            $entity->setDateEnvoi($this->now());
            $entity->setUtilisateurEnvoi($this->security->getUser());
            $this->verrouillerEvenements($entity);
        } else {
            if (!$resource->envoyee && null !== $entity->getDateEnvoi()) {
                $entity->setDateEnvoi(null);
                $entity->setUtilisateurEnvoi(null);
                $this->deverrouillerEvenements($entity);
            }
        }

        $this->periodeRepository->save($entity, true);

        return $entity;
    }

    /**
     * @param PeriodeRHEntity $periodeRH
     * @return void
     */
    private function verrouillerEvenements(PeriodeRHEntity $periodeRH): void
    {
        foreach ($this->evenementRepository->findAllNotLockedBefore($periodeRH->getFin()) as $evenement) {
            //On annule automatiquement tout ce qui aurait dû être validé auparavant !
            if (($evenement->getType()->isAvecValidation() && null == $evenement->getDateValidation())) {
                $evenement->setDateAnnulation($this->now());
                // on indique que ce n'est pas fait par un utilisateur mais automatiquement, au cas où on voudrait les retrouver
                $evenement->setUtilisateurModification(null);
                continue;
            }
            //on ne verrouille que ce qui a un intervenant
            if (null !== $evenement->getIntervenant()) {
                $evenement->setPeriodePriseEnCompteRH($periodeRH);
            }
        }


    }

    private function deverrouillerEvenements(?PeriodeRHEntity $entity): void
    {
        foreach ($entity->getEvenements() as $evenement) {
            $entity->removeEvenement($evenement);
        }
    }


    /**
     * @return PeriodeRHEntity|null
     */
    public function dernierebloquee(): ?PeriodeRHEntity
    {
        $locked = $this->periodeRepository->locked();

        return $locked[0] ?? null;

    }

    /**
     * @param DateTimeInterface $debut
     * @param DateTimeInterface $fin
     * @param bool $versionFinanciere si oui, on se base sur la date de fin de la période
     * @return PeriodeRHEntity[] la liste des périodes comprises entre les deux dates
     */
    public function periodesDansIntervalle(DateTimeInterface $debut, DateTimeInterface $fin, bool $versionFinanciere = false): array
    {
        $chevauchements = $this->periodeRepository->chevauchements($debut, $fin);
        if ($versionFinanciere) {
            return array_filter(
                $chevauchements,
                fn(PeriodeRHEntity $periode) => $periode->getFin() >= $debut && $periode->getFin() <= $fin
            );
        }
        return $chevauchements;

    }

}