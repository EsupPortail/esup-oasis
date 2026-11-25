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

use App\Repository\EntretienRepository;
use App\State\EntityToResourceTransformer;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ORM\Entity(repositoryClass: EntretienRepository::class)]
#[Map(target: \App\ApiResource\Entretien::class, transform: [EntityToResourceTransformer::class, 'entityToResource'])]
class Entretien
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    #[Map(if: false)]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Map(if: false)]
    private ?string $commentaire = null;

    #[ORM\OneToOne(inversedBy: 'entretien', cascade: ['persist', 'remove'])]
    #[Map(if: false)]
    private ?Fichier $fichier = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Map(if: false)]
    private ?DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'entretiens')]
    #[ORM\JoinColumn(nullable: false)]
    #[Map(if: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Map(if: false)]
    private ?Utilisateur $gestionnaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getFichier(): ?Fichier
    {
        return $this->fichier;
    }

    public function setFichier(?Fichier $fichier): static
    {
        $this->fichier = $fichier;
        if (null !== $fichier && $fichier->getEntretien() !== $this) {
            $fichier->setEntretien($this);
        }

        return $this;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): static
    {
        $this->date = DateTime::createFromInterface($date);

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getGestionnaire(): ?Utilisateur
    {
        return $this->gestionnaire;
    }

    public function setGestionnaire(?Utilisateur $gestionnaire): static
    {
        $this->gestionnaire = $gestionnaire;

        return $this;
    }
}
