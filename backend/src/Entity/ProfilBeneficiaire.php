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

use App\Repository\ProfilBeneficiaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfilBeneficiaireRepository::class)]
class ProfilBeneficiaire
{
    public const int A_DETERMINER = -1;
    public const int HANDICAP_PERMANENT = 1;
    public const int INCAPACITE_TEMPORAIRE = 2;
    const int SPORTIF_HAUT_NIVEAU = 3;
    const int SPORTIF_BON_NIVEAU = 5;
    const int SPORTIF_TRES_BON_NIVEAU = 4;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $actif = null;

    #[ORM\OneToMany(mappedBy: 'profil', targetEntity: Beneficiaire::class)]
    private Collection $beneficiaires;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $avecTypologie = false;

    #[ORM\ManyToMany(targetEntity: TypeDemande::class, mappedBy: 'profilsAssocies')]
    private Collection $typesDemandes;

    #[ORM\ManyToMany(targetEntity: Charte::class, mappedBy: 'profilsAssocies')]
    private Collection $chartes;

    public function __construct()
    {
        $this->beneficiaires = new ArrayCollection();
        $this->typesDemandes = new ArrayCollection();
        $this->chartes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * @return Collection<int, Beneficiaire>
     */
    public function getBeneficiaires(): Collection
    {
        return $this->beneficiaires;
    }

    public function addBeneficiaire(Beneficiaire $beneficiaire): self
    {
        if (!$this->beneficiaires->contains($beneficiaire)) {
            $this->beneficiaires->add($beneficiaire);
            $beneficiaire->setProfil($this);
        }

        return $this;
    }

    public function removeBeneficiaire(Beneficiaire $beneficiaire): self
    {
        if ($this->beneficiaires->removeElement($beneficiaire)) {
            // set the owning side to null (unless already changed)
            if ($beneficiaire->getProfil() === $this) {
                $beneficiaire->setProfil(null);
            }
        }

        return $this;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function isAvecTypologie(): ?bool
    {
        return $this->avecTypologie;
    }

    public function setAvecTypologie(bool $avecTypologie): static
    {
        $this->avecTypologie = $avecTypologie;

        return $this;
    }

    /**
     * @return Collection<int, TypeDemande>
     */
    public function getTypesDemandes(): Collection
    {
        return $this->typesDemandes;
    }

    public function addTypesDemande(TypeDemande $typesDemande): static
    {
        if (!$this->typesDemandes->contains($typesDemande)) {
            $this->typesDemandes->add($typesDemande);
            $typesDemande->addProfilAssocie($this);
        }

        return $this;
    }

    public function removeTypesDemande(TypeDemande $typesDemande): static
    {
        if ($this->typesDemandes->removeElement($typesDemande)) {
            $typesDemande->removeProfilAssocie($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Charte>
     */
    public function getChartes(): Collection
    {
        return $this->chartes;
    }

    public function addCharte(Charte $charte): static
    {
        if (!$this->chartes->contains($charte)) {
            $this->chartes->add($charte);
            $charte->addProfilsAssocy($this);
        }

        return $this;
    }

    public function removeCharte(Charte $charte): static
    {
        if ($this->chartes->removeElement($charte)) {
            $charte->removeProfilsAssocy($this);
        }

        return $this;
    }
}
