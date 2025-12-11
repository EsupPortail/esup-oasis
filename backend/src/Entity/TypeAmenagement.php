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

use App\Repository\TypeAmenagementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeAmenagementRepository::class)]
class TypeAmenagement
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $libelleLong = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $actif = true;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $pedagogique = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $examens = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $aideHumaine = false;

    #[ORM\ManyToMany(targetEntity: Reponse::class, mappedBy: 'typesAmenagement')]
    private Collection $reponses;

    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Amenagement::class, orphanRemoval: true)]
    private Collection $amenagements;

    #[ORM\ManyToOne(inversedBy: 'typesAmenagement')]
    private ?CategorieAmenagement $categorie = null;

    #[ORM\Column]
    private ?bool $decision = null;


    public function __construct()
    {
        $this->reponses = new ArrayCollection();
        $this->amenagements = new ArrayCollection();
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

    public function isPedagogique(): ?bool
    {
        return $this->pedagogique;
    }

    public function setPedagogique(bool $pedagogique): static
    {
        $this->pedagogique = $pedagogique;

        return $this;
    }

    public function isExamens(): ?bool
    {
        return $this->examens;
    }

    public function setExamens(bool $examens): static
    {
        $this->examens = $examens;

        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->addAmenagement($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            $reponse->removeAmenagement($this);
        }

        return $this;
    }

    public function isAideHumaine(): ?bool
    {
        return $this->aideHumaine;
    }

    public function setAideHumaine(bool $aideHumaine): static
    {
        $this->aideHumaine = $aideHumaine;

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
            $amenagement->setType($this);
        }

        return $this;
    }

    public function removeAmenagement(Amenagement $amenagement): static
    {
        if ($this->amenagements->removeElement($amenagement)) {
            // set the owning side to null (unless already changed)
            if ($amenagement->getType() === $this) {
                $amenagement->setType(null);
            }
        }

        return $this;
    }

    public function getCategorie(): ?CategorieAmenagement
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieAmenagement $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getLibelleLong(): ?string
    {
        return $this->libelleLong;
    }

    public function setLibelleLong(?string $libelleLong): static
    {
        $this->libelleLong = $libelleLong;

        return $this;
    }

    public function isDecision(): ?bool
    {
        return $this->decision;
    }

    public function setDecision(bool $decision): static
    {
        $this->decision = $decision;

        return $this;
    }
}
