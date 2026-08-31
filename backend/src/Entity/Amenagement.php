<?php /*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 *//*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

/** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Entity;

use App\Repository\AmenagementRepository;
use App\State\EntityToResourceTransformer;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ORM\Entity(repositoryClass: AmenagementRepository::class)]
#[Map(target: \App\ApiResource\Amenagement::class, transform: [EntityToResourceTransformer::class, 'entityToResource'])]
class Amenagement implements BeneficiairesManagerInterface
{
    use ClockAwareTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    #[Map(if: false)]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'amenagements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Map(if: false)]
    private ?TypeAmenagement $type = null;

    #[ORM\Column]
    #[Map(if: false)]
    private ?bool $semestre1 = null;

    #[ORM\Column]
    #[Map(if: false)]
    private ?bool $semestre2 = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Map(if: false)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Map(if: false)]
    private ?DateTimeInterface $fin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Map(if: false)]
    private ?string $commentaire = null;

    #[ORM\ManyToMany(targetEntity: Beneficiaire::class, inversedBy: 'amenagements')]
    #[Map(if: false)]
    private Collection $beneficiaires;

    #[ORM\ManyToOne(inversedBy: 'amenagements')]
    #[Map(if: false)]
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
            default => DateTime::createFromInterface($debut),
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
            default => DateTime::createFromInterface($fin),
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

    #[Override]
    public function canHaveBeneficiaire(Beneficiaire $beneficiaire): bool
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
        // debut et fin sont des dates (granularité jour) : elles hydratent à
        // minuit. On compare au début de la journée courante pour que la fin,
        // inclusive, couvre tout son dernier jour au lieu d'expirer à 00:00.
        $today = $this->now()->setTime(0, 0);
        return $this->getDebut() <= $today && (null === $this->getFin() || $this->getFin() >= $today);
    }
}
