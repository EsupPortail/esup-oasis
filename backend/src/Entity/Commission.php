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

use App\Repository\CommissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionRepository::class)]
class Commission
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $actif = null;

    #[ORM\OneToMany(mappedBy: 'commission', targetEntity: MembreCommission::class, orphanRemoval: true)]
    private Collection $membres;

    #[ORM\OneToMany(mappedBy: 'commission', targetEntity: CampagneDemande::class, orphanRemoval: true)]
    private Collection $campagnes;

    public function __construct()
    {
        $this->membres = new ArrayCollection();
        $this->campagnes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * @return Collection<int, MembreCommission>
     */
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    public function addMembre(MembreCommission $membreCommission): static
    {
        if (!$this->membres->contains($membreCommission)) {
            $this->membres->add($membreCommission);
            $membreCommission->setCommission($this);
        }

        return $this;
    }

    public function removeMembre(MembreCommission $membreCommission): static
    {
        if ($this->membres->removeElement($membreCommission)) {
            // set the owning side to null (unless already changed)
            if ($membreCommission->getCommission() === $this) {
                $membreCommission->setCommission(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CampagneDemande>
     */
    public function getCampagnes(): Collection
    {
        return $this->campagnes;
    }

    public function addCampagne(CampagneDemande $campagne): static
    {
        if (!$this->campagnes->contains($campagne)) {
            $this->campagnes->add($campagne);
            $campagne->setCommission($this);
        }

        return $this;
    }

    public function removeCampagne(CampagneDemande $campagne): static
    {
        if ($this->campagnes->removeElement($campagne)) {
            // set the owning side to null (unless already changed)
            if ($campagne->getCommission() === $this) {
                $campagne->setCommission(null);
            }
        }

        return $this;
    }
}
