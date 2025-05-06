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

use App\Repository\InterventionForfaitRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterventionForfaitRepository::class)]
class InterventionForfait implements BeneficiairesManagerInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'interventionsForfait')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Intervenant $intervenant = null;

    #[ORM\ManyToOne(inversedBy: 'interventionsForfait')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PeriodeRH $periode = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeEvenement $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 1)]
    private ?string $heures = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateModification = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateurCreation = null;

    #[ORM\ManyToOne]
    private ?Utilisateur $utilisateurModification = null;

    #[ORM\ManyToMany(targetEntity: Beneficiaire::class, inversedBy: 'interventionsForfait')]
    private Collection $beneficiaires;

    public function __construct()
    {
        $this->beneficiaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntervenant(): ?Intervenant
    {
        return $this->intervenant;
    }

    public function setIntervenant(?Intervenant $intervenant): static
    {
        $this->intervenant = $intervenant;

        return $this;
    }

    public function getPeriode(): ?PeriodeRH
    {
        return $this->periode;
    }

    public function setPeriode(?PeriodeRH $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

    public function getType(): ?TypeEvenement
    {
        return $this->type;
    }

    public function setType(?TypeEvenement $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getHeures(): ?string
    {
        return $this->heures;
    }

    public function setHeures(string $heures): static
    {
        $this->heures = $heures;

        return $this;
    }

    public function getDateCreation(): ?DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = DateTime::createFromInterface($dateCreation);

        return $this;
    }

    public function getDateModification(): ?DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(DateTimeInterface $dateModification): static
    {
        $this->dateModification = DateTime::createFromInterface($dateModification);

        return $this;
    }

    public function getUtilisateurCreation(): ?Utilisateur
    {
        return $this->utilisateurCreation;
    }

    public function setUtilisateurCreation(?Utilisateur $utilisateurCreation): static
    {
        $this->utilisateurCreation = $utilisateurCreation;

        return $this;
    }

    public function getUtilisateurModification(): ?Utilisateur
    {
        return $this->utilisateurModification;
    }

    public function setUtilisateurModification(?Utilisateur $utilisateurModification): static
    {
        $this->utilisateurModification = $utilisateurModification;

        return $this;
    }

    /**
     * @return Collection<int, Beneficiaire>
     */
    public function getBeneficiaires(): Collection
    {
        return $this->beneficiaires;
    }

    public function addBeneficiaire(Beneficiaire $beneficiaire): static
    {
        if (!$this->beneficiaires->contains($beneficiaire)) {
            $this->beneficiaires->add($beneficiaire);
        }

        return $this;
    }

    public function removeBeneficiaire(Beneficiaire $beneficiaire): static
    {
        $this->beneficiaires->removeElement($beneficiaire);

        return $this;
    }

    public function canHaveBeneficiaire(Beneficiaire $beneficiaire): bool
    {
        if ($beneficiaire->getDebut() >= $this->getPeriode()->getDebut() &&
            $beneficiaire->getDebut() < $this->getPeriode()->getFin()) {
            return true;
        }

        if ($this->getPeriode()->getDebut() >= $beneficiaire->getDebut() &&
            ($this->getPeriode()->getDebut() < $beneficiaire->getFin() || null == $beneficiaire->getFin())) {
            return true;
        }

        return false;
    }

    public function getDureeEnHeures(): string
    {
        return $this->getHeures();
    }
}
