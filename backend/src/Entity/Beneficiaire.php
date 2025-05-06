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

use App\Repository\BeneficiaireRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BeneficiaireRepository::class)]
class Beneficiaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(fetch: "EAGER", inversedBy: 'beneficiaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProfilBeneficiaire $profil = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $fin = null;

    #[ORM\ManyToOne(inversedBy: 'beneficiaires')]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $utilisateur;

    #[ORM\ManyToMany(targetEntity: Evenement::class, mappedBy: 'beneficiaires')]
    private Collection $evenements;

    #[ORM\ManyToOne(inversedBy: 'beneficiairesGeres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $gestionnaire = null;

    #[ORM\ManyToMany(targetEntity: TypologieHandicap::class, inversedBy: 'beneficiaires')]
    private Collection $typologies;

    #[ORM\ManyToMany(targetEntity: InterventionForfait::class, mappedBy: 'beneficiaires')]
    private Collection $interventionsForfait;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $avecAccompagnement = true;

    #[ORM\OneToOne(inversedBy: 'beneficiaire', cascade: ['persist'])]
    private ?Demande $demande = null;

    #[ORM\ManyToMany(targetEntity: Amenagement::class, mappedBy: 'beneficiaires')]
    private Collection $amenagements;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'beneficiaires')]
    private Collection $tags;


    public function __construct()
    {
        $this->evenements = new ArrayCollection();
        $this->typologies = new ArrayCollection();
        $this->interventionsForfait = new ArrayCollection();
        $this->amenagements = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfil(): ?ProfilBeneficiaire
    {
        return $this->profil;
    }

    public function setProfil(?ProfilBeneficiaire $profil): self
    {
        $this->profil = $profil;

        return $this;
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

    public function setFin(?DateTimeInterface $fin): self
    {
        $this->fin = match ($fin) {
            null => null,
            default => DateTime::createFromInterface($fin)
        };

        return $this;
    }


    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

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
            $evenement->addBeneficiaire($this);
        }

        return $this;
    }

    public function removeEvenement(Evenement $evenement): self
    {
        if ($this->evenements->removeElement($evenement)) {
            $evenement->removeBeneficiaire($this);
        }

        return $this;
    }

    public function getGestionnaire(): ?Utilisateur
    {
        return $this->gestionnaire;
    }

    public function setGestionnaire(?Utilisateur $gestionnaire): self
    {
        $this->gestionnaire = $gestionnaire;

        return $this;
    }

    /**
     * @return Collection<int, TypologieHandicap>
     */
    public function getTypologies(): Collection
    {
        return $this->typologies;
    }

    public function addTypology(TypologieHandicap $typology): static
    {
        if (!$this->typologies->contains($typology)) {
            $this->typologies->add($typology);
        }

        return $this;
    }

    public function removeTypology(TypologieHandicap $typology): static
    {
        $this->typologies->removeElement($typology);

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
            $interventionsForfait->addBeneficiaire($this);
        }

        return $this;
    }

    public function removeInterventionsForfait(InterventionForfait $interventionsForfait): static
    {
        if ($this->interventionsForfait->removeElement($interventionsForfait)) {
            $interventionsForfait->removeBeneficiaire($this);
        }

        return $this;
    }

    public function isAvecAccompagnement(): ?bool
    {
        return $this->avecAccompagnement;
    }

    public function setAvecAccompagnement(bool $avecAccompagnement): static
    {
        $this->avecAccompagnement = $avecAccompagnement;

        return $this;
    }

    public function getDemande(): ?Demande
    {
        return $this->demande;
    }

    public function setDemande(?Demande $demande): static
    {
        $this->demande = $demande;

        return $this;
    }

    /**
     * @return Collection<int, Amenagement>
     */
    public function getAmenagements(): Collection
    {
        return $this->amenagements;
    }

    public function addAmenagement(Amenagement $amenagement): static
    {
        if (!$this->amenagements->contains($amenagement)) {
            $this->amenagements->add($amenagement);
            $amenagement->addBeneficiaire($this);
        }

        return $this;
    }

    public function removeAmenagement(Amenagement $amenagement): static
    {
        if ($this->amenagements->removeElement($amenagement)) {
            $amenagement->removeBeneficiaire($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getAmenagementsActifs(): array
    {
        return array_filter(
            $this->getAmenagements()->toArray(),
            fn(Amenagement $amenagement) => $amenagement->isActif()
        );
    }


    /**
     * @param DateTimeInterface $debut
     * @param DateTimeInterface $fin
     * @return iterable<Amenagement>
     */
    public function getAmenagementsParIntervalle(DateTimeInterface $debut, DateTimeInterface $fin): iterable
    {
        foreach ($this->getAmenagements() as $amenagement) {
            if (($amenagement->getDebut() <= $debut && ($amenagement->getFin() === null || $amenagement->getFin() > $debut))
                || ($debut <= $amenagement->getDebut() && $fin > $amenagement->getDebut())) {
                yield $amenagement;
            }
        }
    }
}
