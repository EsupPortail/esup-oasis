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

namespace App\Entity;

use App\Repository\PeriodeRHRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeriodeRHRepository::class)]
class PeriodeRH
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $fin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $butoir = null;

    #[ORM\OneToMany(mappedBy: 'periodePriseEnCompteRH', targetEntity: Evenement::class)]
    private Collection $evenements;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateEnvoi = null;

    #[ORM\ManyToOne]
    private ?Utilisateur $utilisateurEnvoi = null;

    #[ORM\OneToMany(mappedBy: 'periode', targetEntity: InterventionForfait::class)]
    private Collection $interventionsForfait;

    public function __construct()
    {
        $this->evenements = new ArrayCollection();
        $this->interventionsForfait = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDebut(): ?DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(DateTimeInterface $debut): self
    {
        $this->debut = DateTime::createFromInterface($debut);

        return $this;
    }

    public function getFin(): ?DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(DateTimeInterface $fin): self
    {
        $this->fin = DateTime::createFromInterface($fin);

        return $this;
    }

    public function getButoir(): ?DateTimeInterface
    {
        return $this->butoir;
    }

    public function setButoir(DateTimeInterface $butoir): self
    {
        $this->butoir = DateTime::createFromInterface($butoir);

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): self
    {
        if (!$this->evenements->contains($evenement)) {
            $this->evenements->add($evenement);
            $evenement->setPeriodePriseEnCompteRH($this);
        }

        return $this;
    }

    public function removeEvenement(Evenement $evenement): self
    {
        if ($this->evenements->removeElement($evenement)) {
            // set the owning side to null (unless already changed)
            if ($evenement->getPeriodePriseEnCompteRH() === $this) {
                $evenement->setPeriodePriseEnCompteRH(null);
            }
        }

        return $this;
    }

    public function getDateEnvoi(): ?DateTimeInterface
    {
        return $this->dateEnvoi;
    }

    public function setDateEnvoi(?DateTimeInterface $dateEnvoi): self
    {
        $this->dateEnvoi = match ($dateEnvoi) {
            null => null,
            default => DateTime::createFromInterface($dateEnvoi)
        };

        return $this;
    }

    public function getUtilisateurEnvoi(): ?Utilisateur
    {
        return $this->utilisateurEnvoi;
    }

    public function setUtilisateurEnvoi(?Utilisateur $utilisateurEnvoi): self
    {
        $this->utilisateurEnvoi = $utilisateurEnvoi;

        return $this;
    }

    /**
     * @return Collection<int, InterventionForfait>
     */
    public function getInterventionsForfait(): Collection
    {
        return $this->interventionsForfait;
    }

    public function addInterventionsForfait(InterventionForfait $interventionsForfait): static
    {
        if (!$this->interventionsForfait->contains($interventionsForfait)) {
            $this->interventionsForfait->add($interventionsForfait);
            $interventionsForfait->setPeriode($this);
        }

        return $this;
    }

    public function removeInterventionsForfait(InterventionForfait $interventionsForfait): static
    {
        if ($this->interventionsForfait->removeElement($interventionsForfait)) {
            // set the owning side to null (unless already changed)
            if ($interventionsForfait->getPeriode() === $this) {
                $interventionsForfait->setPeriode(null);
            }
        }

        return $this;
    }
}
