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

use App\Repository\SportifHautNiveauRepository;
use App\State\EntityToResourceTransformer;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\TargetClass;

#[ORM\Entity(repositoryClass: SportifHautNiveauRepository::class)]
#[UniqueEntity('identifiantExterne')]
#[Map(target: \App\ApiResource\SportifHautNiveau::class, transform: [
    EntityToResourceTransformer::class,
    'entityToResource',
])]
class SportifHautNiveau
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    #[Map(if: false)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Map(if: new TargetClass(self::class))]
    private ?string $identifiantExterne = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Map(if: new TargetClass(self::class))]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Map(if: new TargetClass(self::class))]
    private ?string $prenom = null;

    #[ORM\Column(nullable: true)]
    #[Map(if: new TargetClass(self::class))]
    private ?int $anneeNaissance = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifiantExterne(): ?string
    {
        return $this->identifiantExterne;
    }

    public function setIdentifiantExterne(string $identifiantExterne): static
    {
        $this->identifiantExterne = $identifiantExterne;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getAnneeNaissance(): ?int
    {
        return $this->anneeNaissance;
    }

    public function setAnneeNaissance(?int $anneeNaissance): static
    {
        $this->anneeNaissance = $anneeNaissance;

        return $this;
    }
}
