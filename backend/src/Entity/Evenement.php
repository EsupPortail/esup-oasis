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

use App\Repository\EvenementRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement implements BeneficiairesManagerInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'evenements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeEvenement $type = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'evenements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campus $campus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $salle = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $fin = null;

    #[ORM\ManyToMany(targetEntity: TypeEquipement::class, inversedBy: 'evenements')]
    private Collection $equipements;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $tempsPreparation = 0;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $tempsSupplementaire = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateAnnulation = null;

    #[ORM\ManyToMany(targetEntity: Beneficiaire::class, inversedBy: 'evenements')]
    private Collection $beneficiaires;

    #[ORM\ManyToOne(inversedBy: 'interventions')]
    private ?Intervenant $intervenant = null;

    #[ORM\ManyToMany(targetEntity: Intervenant::class, inversedBy: 'suppleances')]
    #[ORM\JoinTable(name: 'evenement_intervenant')]
    private Collection $suppleants;

    #[ORM\ManyToOne(inversedBy: 'evenements')]
    private ?PeriodeRH $periodePriseEnCompteRH = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateValidation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateModification = null;

    #[ORM\ManyToOne(inversedBy: 'evenementsCrees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateurCreation = null;

    #[ORM\ManyToOne(inversedBy: 'evenementsModifies')]
    private ?Utilisateur $utilisateurModification = null;

    #[ORM\ManyToMany(targetEntity: Utilisateur::class)]
    private Collection $enseignants;

    public function __construct()
    {
        $this->beneficiaires = new ArrayCollection();
        $this->suppleants = new ArrayCollection();
        $this->equipements = new ArrayCollection();
        $this->enseignants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?TypeEvenement
    {
        return $this->type;
    }

    public function setType(?TypeEvenement $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): self
    {
        $this->campus = $campus;

        return $this;
    }

    public function getSalle(): ?string
    {
        return $this->salle;
    }

    public function setSalle(?string $salle): self
    {
        $this->salle = $salle;

        return $this;
    }

    /**
     * @return Collection<int, TypeEquipement>
     */
    public function getEquipements(): Collection
    {
        return $this->equipements;
    }

    public function addEquipement(TypeEquipement $equipement): self
    {
        if (!$this->equipements->contains($equipement)) {
            $this->equipements->add($equipement);
        }

        return $this;
    }

    public function removeEquipement(TypeEquipement $equipement): self
    {
        $this->equipements->removeElement($equipement);

        return $this;
    }

    public function getDateAnnulation(): ?DateTimeInterface
    {
        return $this->dateAnnulation;
    }

    public function setDateAnnulation(?DateTimeInterface $dateAnnulation): self
    {
        $this->dateAnnulation = match ($dateAnnulation) {
            null => null,
            default => DateTime::createFromInterface($dateAnnulation)
        };

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
        }

        return $this;
    }

    public function removeBeneficiaire(Beneficiaire $beneficiaire): self
    {
        $this->beneficiaires->removeElement($beneficiaire);

        return $this;
    }

    public function getIntervenant(): ?Intervenant
    {
        return $this->intervenant;
    }

    public function setIntervenant(?Intervenant $intervenant): self
    {
        $this->intervenant = $intervenant;

        return $this;
    }

    /**
     * @return Collection<int, Intervenant>
     */
    public function getSuppleants(): Collection
    {
        return $this->suppleants;
    }

    public function addSuppleant(Intervenant $suppleant): self
    {
        if (!$this->suppleants->contains($suppleant)) {
            $this->suppleants->add($suppleant);
        }

        return $this;
    }

    public function removeSuppleant(Intervenant $suppleant): self
    {
        $this->suppleants->removeElement($suppleant);

        return $this;
    }

    public function getPeriodePriseEnCompteRH(): ?PeriodeRH
    {
        return $this->periodePriseEnCompteRH;
    }

    public function setPeriodePriseEnCompteRH(?PeriodeRH $periodePriseEnCompteRH): self
    {
        $this->periodePriseEnCompteRH = $periodePriseEnCompteRH;

        return $this;
    }

    public function getDateValidation(): ?DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?DateTimeInterface $dateValidation): self
    {
        $this->dateValidation = match ($dateValidation) {
            null => null,
            default => DateTime::createFromInterface($dateValidation)
        };

        return $this;
    }

    public function getDateCreation(): ?DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = DateTime::createFromInterface($dateCreation);

        return $this;
    }

    public function getDateModification(): ?DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?DateTimeInterface $dateModification): self
    {
        $this->dateModification = match ($dateModification) {
            null => null,
            default => DateTime::createFromInterface($dateModification)
        };

        return $this;
    }

    public function getUtilisateurCreation(): ?Utilisateur
    {
        return $this->utilisateurCreation;
    }

    public function setUtilisateurCreation(?Utilisateur $utilisateurCreation): self
    {
        $this->utilisateurCreation = $utilisateurCreation;

        return $this;
    }

    public function getUtilisateurModification(): ?Utilisateur
    {
        return $this->utilisateurModification;
    }

    public function setUtilisateurModification(?Utilisateur $utilisateurModification): self
    {
        $this->utilisateurModification = $utilisateurModification;

        return $this;
    }

    /**
     * @param Beneficiaire $beneficiaire
     * @return bool
     */
    public function canHaveBeneficiaire(Beneficiaire $beneficiaire): bool
    {
        if ($beneficiaire->getDebut() > $this->getDebut() || (null !== $beneficiaire->getFin() && $beneficiaire->getFin() < $this->getFin())) {
            return false;
        }
        return true;
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

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getEnseignants(): Collection
    {
        return $this->enseignants;
    }

    public function addEnseignant(Utilisateur $enseignant): static
    {
        if (!$this->enseignants->contains($enseignant)) {
            $this->enseignants->add($enseignant);
        }

        return $this;
    }

    public function removeEnseignant(Utilisateur $enseignant): static
    {
        $this->enseignants->removeElement($enseignant);

        return $this;
    }

    /**
     * @return string BCMath-produced string
     */
    public function getDureeEnHeures(): string
    {
        bcscale(8);//précision des calculs, on vise large

        $debutEffectif = (clone $this->getDebut())->modify('-' . $this->getTempsPreparation() . ' minutes');
        $finEffective = (clone $this->getFin())->modify('+' . $this->getTempsSupplementaire() . ' minutes');
        $dureeEffective = $finEffective->diff($debutEffectif);

        return bcadd($dureeEffective->h, bcdiv($dureeEffective->i, 60));
    }

    public function getTempsPreparation(): ?int
    {
        return $this->tempsPreparation;
    }

    public function setTempsPreparation(int $tempsPreparation): self
    {
        $this->tempsPreparation = $tempsPreparation;

        return $this;
    }

    public function getTempsSupplementaire(): ?int
    {
        return $this->tempsSupplementaire;
    }

    public function setTempsSupplementaire(int $tempsSupplementaire): self
    {
        $this->tempsSupplementaire = $tempsSupplementaire;

        return $this;
    }
}
