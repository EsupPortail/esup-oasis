<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */

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

use App\Repository\AmenagementRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Clock\ClockAwareTrait;

#[ORM\Entity(repositoryClass: AmenagementRepository::class)]
class Amenagement implements BeneficiairesManagerInterface
{
    use ClockAwareTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'amenagements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeAmenagement $type = null;

    #[ORM\Column]
    private ?bool $semestre1 = null;

    #[ORM\Column]
    private ?bool $semestre2 = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $fin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToMany(targetEntity: Beneficiaire::class, inversedBy: 'amenagements')]
    private Collection $beneficiaires;

    #[ORM\ManyToOne(inversedBy: 'amenagements')]
    private ?TypeSuiviAmenagement $suivi = null;

    public function __construct()
    {
        $this->beneficiaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?TypeAmenagement
    {
        return $this->type;
    }

    public function setType(?TypeAmenagement $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isSemestre1(): ?bool
    {
        return $this->semestre1;
    }

    public function setSemestre1(bool $semestre1): static
    {
        $this->semestre1 = $semestre1;

        return $this;
    }

    public function isSemestre2(): ?bool
    {
        return $this->semestre2;
    }

    public function setSemestre2(bool $semestre2): static
    {
        $this->semestre2 = $semestre2;

        return $this;
    }

    public function getDebut(): ?DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(?DateTimeInterface $debut): static
    {
        $this->debut = match ($debut) {
            null => null,
            default => DateTime::createFromInterface($debut)
        };

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

    /**
     * @return Collection<int, Beneficiaire>
     */
    public function getBeneficiaires(): Collection
    {
        return $this->beneficiaires;
    }

    public function addBeneficiaire(Beneficiaire $beneficiaire): static
    {
        if (!$this->beneficiaires->contains($beneficiaire)) {
            $this->beneficiaires->add($beneficiaire);
        }

        return $this;
    }

    public function removeBeneficiaire(Beneficiaire $beneficiaire): static
    {
        $this->beneficiaires->removeElement($beneficiaire);

        return $this;
    }

    #[Override] public function canHaveBeneficiaire(Beneficiaire $beneficiaire): bool
    {
        //On vérifie juste si on n'essaye pas de modifier le passé
        if ($beneficiaire->getFin() !== null && $beneficiaire->getFin() < $this->now()) {
            return false;
        }
        return true;

    }

    public function getSuivi(): ?TypeSuiviAmenagement
    {
        return $this->suivi;
    }

    public function setSuivi(?TypeSuiviAmenagement $suivi): static
    {
        $this->suivi = $suivi;

        return $this;
    }

    public function isActif(): bool
    {
        $now = $this->now();
        return $this->getDebut() <= $now && (null === $this->getFin() || $this->getFin() >= $now);
    }
}
