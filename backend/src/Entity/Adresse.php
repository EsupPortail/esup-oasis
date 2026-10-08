<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Adresse postale embarquée dans Utilisateur. Modélisée en embeddable Doctrine
 * pour pouvoir la réutiliser sur d'autres entités sans dupliquer le jeu de
 * colonnes. Pas exposée comme ressource API : le hook de propriété de la
 * ressource Utilisateur la convertit en tableau associatif, qu'API Platform
 * sérialise en ligne dans la ressource parente.
 */
#[ORM\Embeddable]
class Adresse
{
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $ligne1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $ligne2 = null;

    #[ORM\Column(length: 20, nullable: true)]
    public ?string $codePostal = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $ville = null;

    #[ORM\Column(length: 100, nullable: true)]
    public ?string $pays = null;

    public function getLigne1(): ?string
    {
        return $this->ligne1;
    }

    public function setLigne1(?string $ligne1): static
    {
        $this->ligne1 = $ligne1;
        return $this;
    }

    public function getLigne2(): ?string
    {
        return $this->ligne2;
    }

    public function setLigne2(?string $ligne2): static
    {
        $this->ligne2 = $ligne2;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;
        return $this;
    }

    /**
     * Vrai quand aucun champ ne porte de valeur. Sert à court-circuiter la
     * sérialisation : on préfère exposer null qu'un objet rempli de null.
     */
    public function isEmpty(): bool
    {
        return null === $this->ligne1
            && null === $this->ligne2
            && null === $this->codePostal
            && null === $this->ville
            && null === $this->pays;
    }

    /**
     * Représentation associative simple, sérialisable directement par API
     * Platform dans la ressource parente, plutôt que transformée en référence
     * `@id` de nœud anonyme.
     *
     * @return array{ligne1: ?string, ligne2: ?string, codePostal: ?string, ville: ?string, pays: ?string}
     */
    public function toArray(): array
    {
        return [
            'ligne1' => $this->ligne1,
            'ligne2' => $this->ligne2,
            'codePostal' => $this->codePostal,
            'ville' => $this->ville,
            'pays' => $this->pays,
        ];
    }
}
