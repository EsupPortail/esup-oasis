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

use App\Repository\DecisionAmenagementExamensRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DecisionAmenagementExamensRepository::class)]
class DecisionAmenagementExamens
{

    public const string ETAT_ATTENTE_VALIDATION_CAS = 'ATTENTE_VALIDATION_CAS';
    public const string ETAT_VALIDE = 'VALIDE';
    public const string ETAT_EDITE = 'EDITE';

    public const string ETAT_EDITION_DEMANDEE = 'EDITION_DEMANDEE';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $fin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateModification = null;

    #[ORM\ManyToOne(inversedBy: 'decisionsAmenagementExamens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $beneficiaire = null;

    #[ORM\Column(length: 255)]
    private ?string $etat = null;

    #[ORM\OneToOne(inversedBy: 'decisionAmenagementExamens', cascade: ['persist', 'remove'])]
    private ?Fichier $fichier = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateModification(): ?DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(DateTimeInterface $dateModification): static
    {
        $this->dateModification = DateTime::createFromInterface($dateModification);

        return $this;
    }

    public function getBeneficiaire(): ?Utilisateur
    {
        return $this->beneficiaire;
    }

    public function setBeneficiaire(?Utilisateur $beneficiaire): static
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
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
}
