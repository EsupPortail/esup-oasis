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

use App\Repository\BilanRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BilanRepository::class)]
class Bilan
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'bilan', cascade: ['persist', 'remove'])]
    private ?Fichier $fichier = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $dateDemande = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateGeneration = null;

    #[ORM\ManyToOne(inversedBy: 'bilans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $demandeur = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $fin = null;

    #[ORM\Column(nullable: true)]
    private ?array $parametres = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFichier(): ?Fichier
    {
        return $this->fichier;
    }

    public function setFichier(?Fichier $fichier): static
    {
        $this->fichier = $fichier;

        return $this;
    }

    public function getDateDemande(): ?DateTimeInterface
    {
        return $this->dateDemande;
    }

    public function setDateDemande(DateTimeInterface $dateDemande): static
    {
        $this->dateDemande = DateTime::createFromInterface($dateDemande);

        return $this;
    }

    public function getDemandeur(): ?Utilisateur
    {
        return $this->demandeur;
    }

    public function setDemandeur(?Utilisateur $demandeur): static
    {
        $this->demandeur = $demandeur;

        return $this;
    }

    public function getDebut(): ?DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(DateTimeInterface $debut): static
    {
        $this->debut = $debut;

        return $this;
    }

    public function getFin(): ?DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(DateTimeInterface $fin): static
    {
        $this->fin = $fin;

        return $this;
    }

    public function getDateGeneration(): ?DateTimeInterface
    {
        return $this->dateGeneration;
    }

    public function setDateGeneration(?DateTimeInterface $dateGeneration): static
    {
        $this->dateGeneration = match ($dateGeneration) {
            null => null,
            default => DateTime::createFromInterface($dateGeneration)
        };

        return $this;
    }

    public function getParametres(): ?array
    {
        return $this->parametres;
    }

    public function setParametres(?array $parametres): static
    {
        $this->parametres = $parametres;

        return $this;
    }

    public function getParametre(string $paramName): string|array|null
    {
        return $this->getParametres()[$paramName] ?? null;
    }
}
