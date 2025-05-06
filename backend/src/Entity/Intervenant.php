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

use App\Repository\IntervenantRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Clock\ClockAwareTrait;

#[ORM\Entity(repositoryClass: IntervenantRepository::class)]
class Intervenant
{
    use ClockAwareTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'intervenant', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\OneToMany(mappedBy: 'intervenant', targetEntity: Evenement::class)]
    private Collection $interventions;

    #[ORM\ManyToMany(targetEntity: Evenement::class, mappedBy: 'suppleants')]
    private Collection $suppleances;

    #[ORM\ManyToMany(targetEntity: Competence::class, inversedBy: 'intervenants')]
    private Collection $competences;

    #[ORM\ManyToMany(targetEntity: Campus::class, inversedBy: 'intervenants')]
    private Collection $campuses;

    #[ORM\ManyToMany(targetEntity: TypeEvenement::class, inversedBy: 'intervenants')]
    private Collection $typesEvenements;

    #[ORM\OneToMany(mappedBy: 'intervenant', targetEntity: InterventionForfait::class)]
    private Collection $interventionsForfait;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $fin = null;

    public function __construct()
    {
        $this->interventions = new ArrayCollection();
        $this->suppleances = new ArrayCollection();
        $this->competences = new ArrayCollection();
        $this->campuses = new ArrayCollection();
        $this->typesEvenements = new ArrayCollection();
        $this->interventionsForfait = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
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
    public function getInterventions(): Collection
    {
        return $this->interventions;
    }

    public function addIntervention(Evenement $intervention): self
    {
        if (!$this->interventions->contains($intervention)) {
            $this->interventions->add($intervention);
            $intervention->setIntervenant($this);
        }

        return $this;
    }

    public function removeIntervention(Evenement $intervention): self
    {
        if ($this->interventions->removeElement($intervention)) {
            // set the owning side to null (unless already changed)
            if ($intervention->getIntervenant() === $this) {
                $intervention->setIntervenant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getSuppleances(): Collection
    {
        return $this->suppleances;
    }

    public function addSuppleance(Evenement $suppleance): self
    {
        if (!$this->suppleances->contains($suppleance)) {
            $this->suppleances->add($suppleance);
            $suppleance->addSuppleant($this);
        }

        return $this;
    }

    public function removeSuppleance(Evenement $suppleance): self
    {
        if ($this->suppleances->removeElement($suppleance)) {
            $suppleance->removeSuppleant($this);
        }

        return $this;
    }

    public function isArchive(): ?bool
    {
        return $this->now() >= $this->getFin();
    }


    /**
     * @return Collection<int, Competence>
     */
    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    public function addCompetence(Competence $competence): self
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
        }

        return $this;
    }

    /**
     * @param Competence[] $competences
     * @return void
     */
    public function replaceCompetences(array $competences): self
    {
        foreach ($this->getCompetences() as $competence) {
            if (!in_array($competence, $competences)) {
                $this->removeCompetence($competence);
            }
        }
        foreach ($competences as $competence) {
            $this->addCompetence($competence);
        }
        return $this;
    }

    public function removeCompetence(Competence $competence): self
    {
        $this->competences->removeElement($competence);

        return $this;
    }

    /**
     * @return Collection<int, Campus>
     */
    public function getCampuses(): Collection
    {
        return $this->campuses;
    }

    public function addCampus(Campus $campus): self
    {
        if (!$this->campuses->contains($campus)) {
            $this->campuses->add($campus);
        }

        return $this;
    }

    public function removeCampus(Campus $campus): self
    {
        $this->campuses->removeElement($campus);

        return $this;
    }

    /**
     * @param Campus[] $campuses
     * @return self
     */
    public function replaceCampus(array $campuses): self
    {
        foreach ($this->getCampuses() as $campus) {
            if (!in_array($campus, $campuses)) {
                $this->removeCampus($campus);
            }
        }
        foreach ($campuses as $campus) {
            $this->addCampus($campus);
        }
        return $this;
    }

    /**
     * @return Collection<int, TypeEvenement>
     */
    public function getTypesEvenements(): Collection
    {
        return $this->typesEvenements;
    }

    public function addTypesEvenement(TypeEvenement $typesEvenement): self
    {
        if (!$this->typesEvenements->contains($typesEvenement)) {
            $this->typesEvenements->add($typesEvenement);
        }

        return $this;
    }

    /**
     * @param TypeEvenement[] $typesEvenements
     * @return $this
     */
    public function replaceTypesEvenements(array $typesEvenements): self
    {
        foreach ($this->getTypesEvenements() as $type) {
            if (!in_array($type, $typesEvenements)) {
                $this->removeTypesEvenement($type);
            }
        }
        foreach ($typesEvenements as $type) {
            $this->addTypesEvenement($type);
        }
        return $this;
    }

    public function removeTypesEvenement(TypeEvenement $typesEvenement): self
    {
        $this->typesEvenements->removeElement($typesEvenement);

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
            $interventionsForfait->setIntervenant($this);
        }

        return $this;
    }

    public function removeInterventionsForfait(InterventionForfait $interventionsForfait): static
    {
        if ($this->interventionsForfait->removeElement($interventionsForfait)) {
            // set the owning side to null (unless already changed)
            if ($interventionsForfait->getIntervenant() === $this) {
                $interventionsForfait->setIntervenant(null);
            }
        }

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


}
