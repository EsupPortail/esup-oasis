<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Entity;

use App\Repository\ComposanteRepository;
use App\State\EntityToResourceTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Transform\MapCollection;

#[ORM\Entity(repositoryClass: ComposanteRepository::class)]
#[Map(target: \App\ApiResource\Composante::class, transform: [EntityToResourceTransformer::class, 'entityToResource'])]
class Composante
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    #[Map(if: false)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Map(if: false)]
    private ?string $libelle = null;

    #[ORM\Column(length: 10)]
    #[Map(if: false)]
    private ?string $codeExterne = null;

    /** Circuit du parapheur qui signe les décisions d'aménagements de la composante ; null : envoi par e-mail. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Map(if: false)]
    private ?string $circuitSignature = null;

    #[ORM\OneToMany(targetEntity: Formation::class, mappedBy: 'composante', orphanRemoval: true)]
    #[Map(if: false)]
    private Collection $formations;

    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class, inversedBy: 'composantes')]
    #[Map(if: false)]
    private Collection $referents;

    public function __construct()
    {
        $this->formations = new ArrayCollection();
        $this->referents = new ArrayCollection();
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

    public function getCircuitSignature(): ?string
    {
        return $this->circuitSignature;
    }

    public function setCircuitSignature(?string $circuitSignature): self
    {
        $this->circuitSignature = $circuitSignature;

        return $this;
    }

    public function getCodeExterne(): ?string
    {
        return $this->codeExterne;
    }

    public function setCodeExterne(string $codeExterne): self
    {
        $this->codeExterne = $codeExterne;

        return $this;
    }

    /**
     * @return Collection<int, Formation>
     */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formation $formation): self
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
            $formation->setComposante($this);
        }

        return $this;
    }

    public function removeFormation(Formation $formation): self
    {
        if ($this->formations->removeElement($formation)) {
            // set the owning side to null (unless already changed)
            if ($formation->getComposante() === $this) {
                $formation->setComposante(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getReferents(): Collection
    {
        return $this->referents;
    }

    public function addReferent(Utilisateur $referent): static
    {
        if (!$this->referents->contains($referent)) {
            $this->referents->add($referent);
            $referent->addComposante($this);
        }

        return $this;
    }

    public function removeReferent(Utilisateur $referent): static
    {
        if ($this->referents->removeElement($referent)) {
            $referent->removeComposante($this);
        }

        return $this;
    }
}
