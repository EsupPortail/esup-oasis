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

use App\Repository\EtatDemandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtatDemandeRepository::class)]
class EtatDemande
{
    public const int EN_COURS = 1;
    public const int RECEPTIONNEE = 2;
    public const int CONFORME = 3;
    public const int VALIDEE = 4;
    public const int REFUSEE = 5;
    public const int PROFIL_VALIDE = 6;
    public const int ATTENTE_COMMISSION = 7;
    public const int NON_CONFORME = 8;
    public const int ATTENTE_VALIDATION_CHARTE = 9;
    public const int ATTENTE_VALIDATION_ACCOMPAGNEMENT = 10;

    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $actif = null;

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

    public function setId(?int $id): EtatDemande
    {
        $this->id = $id;
        return $this;
    }
}
