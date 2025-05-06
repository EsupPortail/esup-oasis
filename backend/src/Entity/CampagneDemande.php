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

use App\Repository\CampagneDemandeRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Clock\ClockAwareTrait;

#[ORM\Entity(repositoryClass: CampagneDemandeRepository::class)]
class CampagneDemande
{
    use ClockAwareTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'campagnes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeDemande $typeDemande = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $fin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\OneToMany(mappedBy: 'campagne', targetEntity: Demande::class)]
    private Collection $demandes;

    #[ORM\ManyToOne(inversedBy: 'campagnes')]
    private ?Commission $commission = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateCommission = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneeCible = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateArchivage = null;

    public function __construct()
    {
        $this->demandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeDemande(): ?TypeDemande
    {
        return $this->typeDemande;
    }

    public function setTypeDemande(?TypeDemande $typeDemande): static
    {
        $this->typeDemande = $typeDemande;

        return $this;
    }

    public function getDebut(): ?DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(DateTimeInterface $debut): static
    {
        $this->debut = DateTime::createFromInterface($debut);

        return $this;
    }

    public function getFin(): ?DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(DateTimeInterface $fin): static
    {
        $this->fin = DateTime::createFromInterface($fin);

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, Demande>
     */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(Demande $demande): static
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
            $demande->setCampagne($this);
        }

        return $this;
    }

    public function removeDemande(Demande $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            // set the owning side to null (unless already changed)
            if ($demande->getCampagne() === $this) {
                $demande->setCampagne(null);
            }
        }

        return $this;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): static
    {
        $this->commission = $commission;

        return $this;
    }

    public function getDateCommission(): ?DateTimeInterface
    {
        return $this->dateCommission;
    }

    public function setDateCommission(?DateTimeInterface $dateCommission): static
    {
        $this->dateCommission = match ($dateCommission) {
            null => null,
            default => DateTime::createFromInterface($dateCommission)
        };

        return $this;
    }

    public function estEnCours(): bool
    {
        $now = $this->now();
        return $now >= $this->getDebut() && $now <= $this->getFin();
    }

    public function getAnneeCible(): ?int
    {
        return $this->anneeCible;
    }

    public function setAnneeCible(?int $anneeCible): static
    {
        $this->anneeCible = $anneeCible;

        return $this;
    }

    public function getDemande(?Utilisateur $demandeur): ?Demande
    {
        foreach ($this->getDemandes() as $demande) {
            if ($demande->getDemandeur() === $demandeur) {
                return $demande;
            }
        }
        return null;
    }

    public function commissionAVenir(): bool
    {
        return match ($this->getDateCommission()) {
            null => false,
            default => $this->now() <= $this->getDateCommission()
        };
    }

    public function getDateArchivage(): ?DateTimeInterface
    {
        return $this->dateArchivage;
    }

    public function setDateArchivage(?DateTimeInterface $dateArchivage): static
    {
        $this->dateArchivage = match ($dateArchivage) {
            null => null,
            default => DateTime::createFromInterface($dateArchivage)
        };

        return $this;
    }
}
