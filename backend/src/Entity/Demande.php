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

use App\Repository\DemandeRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeRepository::class)]
class Demande
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CampagneDemande $campagne = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'demandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $demandeur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateDepot = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?EtatDemande $etat = null;

    #[ORM\OneToOne(mappedBy: 'demande', cascade: ['persist'])]
    private ?Beneficiaire $beneficiaire = null;

    #[ORM\OneToMany(mappedBy: 'demande', targetEntity: ModificationEtatDemande::class, orphanRemoval: true)]
    private Collection $modifications;

    #[ORM\OneToMany(mappedBy: 'demande', targetEntity: CharteDemandeur::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $chartes;

    #[ORM\ManyToOne]
    private ?ProfilBeneficiaire $profilAttribue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    public function __construct()
    {
        $this->modifications = new ArrayCollection();
        $this->chartes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampagne(): ?CampagneDemande
    {
        return $this->campagne;
    }

    public function setCampagne(?CampagneDemande $campagne): static
    {
        $this->campagne = $campagne;

        return $this;
    }

    public function getDemandeur(): ?Utilisateur
    {
        return $this->demandeur;
    }

    public function setDemandeur(?Utilisateur $demandeur): static
    {
        $this->demandeur = $demandeur;

        return $this;
    }

    public function getDateDepot(): ?DateTimeInterface
    {
        return $this->dateDepot;
    }

    public function setDateDepot(?DateTimeInterface $dateDepot): static
    {
        $this->dateDepot = match ($dateDepot) {
            null => null,
            default => DateTime::createFromInterface($dateDepot)
        };

        return $this;
    }

    public function getEtat(): ?EtatDemande
    {
        return $this->etat;
    }

    public function setEtat(?EtatDemande $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getBeneficiaire(): ?Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function setBeneficiaire(?Beneficiaire $beneficiaire): static
    {
        // unset the owning side of the relation if necessary
        if ($beneficiaire === null && $this->beneficiaire !== null) {
            $this->beneficiaire->setDemande(null);
        }

        // set the owning side of the relation if necessary
        if ($beneficiaire !== null && $beneficiaire->getDemande() !== $this) {
            $beneficiaire->setDemande($this);
        }

        $this->beneficiaire = $beneficiaire;

        return $this;
    }

    /**
     * @return Collection<int, ModificationEtatDemande>
     */
    public function getModifications(): Collection
    {
        return $this->modifications;
    }

    public function addModification(ModificationEtatDemande $modification): static
    {
        if (!$this->modifications->contains($modification)) {
            $this->modifications->add($modification);
            $modification->setDemande($this);
        }

        return $this;
    }

    public function removeModification(ModificationEtatDemande $modification): static
    {
        if ($this->modifications->removeElement($modification)) {
            // set the owning side to null (unless already changed)
            if ($modification->getDemande() === $this) {
                $modification->setDemande(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CharteDemandeur>
     */
    public function getChartes(): Collection
    {
        return $this->chartes;
    }

    public function addCharte(CharteDemandeur $charte): static
    {
        if (!$this->chartes->contains($charte)) {
            $this->chartes->add($charte);
            $charte->setDemande($this);
        }

        return $this;
    }

    public function removeCharte(CharteDemandeur $charte): static
    {
        if ($this->chartes->removeElement($charte)) {
            // set the owning side to null (unless already changed)
            if ($charte->getDemande() === $this) {
                $charte->setDemande(null);
            }
        }

        return $this;
    }

    public function getProfilAttribue(): ?ProfilBeneficiaire
    {
        return $this->profilAttribue;
    }

    public function setProfilAttribue(?ProfilBeneficiaire $profilAttribue): static
    {
        $this->profilAttribue = $profilAttribue;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

}
