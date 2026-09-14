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

use App\Repository\ParametreRepository;
use App\State\EntityToResourceTransformer;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ORM\Entity(repositoryClass: ParametreRepository::class)]
#[Map(target: Parametre::class, transform: [EntityToResourceTransformer::class, 'entityToResource'])]
class Parametre
{
    use ClockAwareTrait;

    public const string EXPEDITEUR_EMAILS = 'EXPEDITEUR_EMAILS';
    public const string SERVICE_NOM = 'SERVICE_NOM';
    public const string FREQUENCE_RAPPELS = 'FREQUENCE_RAPPELS';
    public const string FREQUENCE_MAJ_INSCRIPTIONS = 'FREQUENCE_MAJ_INSCRIPTIONS';
    public const string FREQUENCE_RAPPEL_ENVOI_RH = 'FREQUENCE_RAPPEL_ENVOI_RH';
    public const string RAPPELS_SANS_INTERVENANTS = 'RAPPELS_SANS_INTERVENANTS';
    public const string COEFFICIENT_CHARGES = 'COEFFICIENT_CHARGES';
    public const string DESTINATAIRES_COPIE_DECISIONS = 'DESTINATAIRES_COPIE_DECISIONS';
    public const string SIGNATURE_DECISIONS = 'SIGNATURE_DECISIONS';
    public const string ROLES_A_JOUR = 'ROLES_A_JOUR';
    public const string APP_TITRE = 'APP_TITRE';
    public const string APP_SERVICE_DENOMINATION = 'APP_SERVICE_DENOMINATION';
    public const string APP_SERVICE_ARTICLE = 'APP_SERVICE_ARTICLE';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    #[Map(if: false)]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Map(if: false)]
    private ?string $cle = null;

    #[ORM\OneToMany(
        targetEntity: ValeurParametre::class,
        mappedBy: 'parametre',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    #[Map(if: false)]
    private Collection $valeursParametres;

    #[ORM\Column(nullable: true, options: ['default' => false])]
    #[Map(if: false)]
    private ?bool $fichier = null;

    public function __construct()
    {
        $this->valeursParametres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCle(): ?string
    {
        return $this->cle;
    }

    public function setCle(string $cle): self
    {
        $this->cle = $cle;

        return $this;
    }

    /**
     * @return Collection<int, ValeurParametre>
     */
    public function getValeursParametres(): Collection
    {
        return $this->valeursParametres;
    }

    public function addValeursParametre(ValeurParametre $valeursParametre): static
    {
        if (!$this->valeursParametres->contains($valeursParametre)) {
            $this->valeursParametres->add($valeursParametre);
            $valeursParametre->setParametre($this);
        }

        return $this;
    }

    public function removeValeursParametre(ValeurParametre $valeursParametre): static
    {
        if ($this->valeursParametres->removeElement($valeursParametre)) {
            // set the owning side to null (unless already changed)
            if ($valeursParametre->getParametre() === $this) {
                $valeursParametre->setParametre(null);
            }
        }

        return $this;
    }

    /**
     * @return ValeurParametre|ValeurParametre[]|null
     */
    public function getValeurCourante(bool $multiple = false): ValeurParametre|array|null
    {
        $now = $this->now();
        $valeurs = [];
        foreach ($this->getValeursParametres() as $valeur) {
            if ($valeur->getDebut() < $now && ($valeur->getFin() === null || $now < $valeur->getFin())) {
                if (!$multiple) {
                    return $valeur;
                }
                $valeurs[] = $valeur;
            }
        }
        return $multiple ? $valeurs : null;
    }

    public function getValeurPourDate(?DateTimeInterface $dateItem)
    {
        foreach ($this->getValeursParametres() as $valeur) {
            if ($valeur->getDebut() < $dateItem && ($valeur->getFin() === null || $dateItem < $valeur->getFin())) {
                return $valeur;
            }
        }
        return null;
    }

    public function isFichier(): ?bool
    {
        return $this->fichier;
    }

    public function setFichier(?bool $fichier): static
    {
        $this->fichier = $fichier;

        return $this;
    }
}
