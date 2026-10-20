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

use App\Repository\EtablissementEnseignementArtistiqueRepository;
use App\State\EntityToResourceTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\TargetClass;

#[ORM\Entity(repositoryClass: EtablissementEnseignementArtistiqueRepository::class)]
#[Map(target: \App\ApiResource\EtablissementEnseignementArtistique::class, transform: [
    EntityToResourceTransformer::class,
    'entityToResource',
])]
class EtablissementEnseignementArtistique
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    #[Map(if: false)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Map(if: new TargetClass(self::class))]
    private ?string $libelle = null;

    #[ORM\Column(options: ['default' => true])]
    #[Map(if: new TargetClass(self::class))]
    private ?bool $actif = null;

    /**
     * @var Collection<int, Reponse>
     */
    #[ORM\ManyToMany(targetEntity: Reponse::class, mappedBy: 'etablissementsEnseignementArtistique')]
    #[Map(if: false)]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
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
            $reponse->addEtablissementEnseignementArtistique($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            $reponse->removeEtablissementEnseignementArtistique($this);
        }

        return $this;
    }
}
