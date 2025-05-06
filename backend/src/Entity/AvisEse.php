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

use App\Repository\AvisEseRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Clock\ClockAwareTrait;

#[ORM\Entity(repositoryClass: AvisEseRepository::class)]
class AvisEse
{
    use ClockAwareTrait;

    public const string ETAT_EN_COURS = 'EN_COURS';
    public const string ETAT_EN_ATTENTE = 'EN_ATTENTE';
    public const string ETAT_AUCUN = 'AUCUN';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $fin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\OneToOne(inversedBy: 'avisEse', cascade: ['persist', 'remove'])]
    private ?Fichier $fichier = null;

    #[ORM\ManyToOne(inversedBy: 'avisEse')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

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

    public function setFin(?DateTimeInterface $fin): static
    {
        $this->fin = match ($fin) {
            null => null,
            default => DateTime::createFromInterface($fin)
        };

        return $this;
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

    public function estActif(?DateTimeInterface $pourDate = null): bool
    {
        return !$this->estEnAttente() && $this->estEnCours($pourDate);
    }

    public function estEnCours($pourDate = null): bool
    {
        $now = ($pourDate !== null) ? $pourDate : $this->now();
        return $now >= $this->getDebut() && (null === $this->getFin() || $now <= $this->getFin());
    }

    public function estEnAttente(): bool
    {
        return null == $this->getFichier();
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }
}
