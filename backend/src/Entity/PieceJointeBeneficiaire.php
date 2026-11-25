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

use App\Repository\PieceJointeBeneficiaireRepository;
use App\State\EntityToResourceTransformer;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ORM\Entity(repositoryClass: PieceJointeBeneficiaireRepository::class)]
#[Map(target: \App\ApiResource\PieceJointeBeneficiaire::class, transform: [
    EntityToResourceTransformer::class,
    'entityToResource',
])]
class PieceJointeBeneficiaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    #[Map(if: false)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Map(if: false)]
    private ?string $libelle = null;

    #[ORM\OneToOne(inversedBy: 'pieceJointeBeneficiaire', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Map(if: false)]
    private ?Fichier $fichier = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Map(if: false)]
    private ?DateTimeInterface $dateDepot = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Map(if: false)]
    private ?Utilisateur $utilisateurCreation = null;

    #[ORM\ManyToOne(inversedBy: 'piecesJointes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Map(if: false)]
    private ?Utilisateur $beneficiaire = null;

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

    public function getFichier(): ?Fichier
    {
        return $this->fichier;
    }

    public function setFichier(Fichier $fichier): static
    {
        $this->fichier = $fichier;
        if ($fichier->getPieceJointeBeneficiaire() !== $this) {
            $fichier->setPieceJointeBeneficiaire($this);
        }

        return $this;
    }

    public function getDateDepot(): ?DateTimeInterface
    {
        return $this->dateDepot;
    }

    public function setDateDepot(DateTimeInterface $dateDepot): static
    {
        $this->dateDepot = DateTime::createFromInterface($dateDepot);

        return $this;
    }

    public function getUtilisateurCreation(): ?Utilisateur
    {
        return $this->utilisateurCreation;
    }

    public function setUtilisateurCreation(?Utilisateur $utilisateurCreation): static
    {
        $this->utilisateurCreation = $utilisateurCreation;

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
}
