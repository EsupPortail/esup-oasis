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

namespace App\MessageHandler;

use App\ApiResource\Amenagement;
use App\ApiResource\BeneficiaireProfil;
use App\ApiResource\Inscription;
use App\ApiResource\TypeAmenagement;
use App\ApiResource\Utilisateur;

class UtilisateurBilanActivite
{
    public int $numero;
    public int $anneeNaissance;
    public ?string $sexe;

    public ?Inscription $derniereInscription;

    public ?string $regimeInscription;

    /**
     * @var BeneficiaireProfil[]
     */
    public array $profils = [];

    /**
     * @var Amenagement[]
     */
    public array $amenagements = [];

    public Utilisateur $gestionnaire;

    public int $nbEntretiens;

    public function avecTypeAmenagement(TypeAmenagement $type): bool
    {
        foreach ($this->amenagements as $amenagement) {
            if ($amenagement->typeAmenagement->id == $type->id) {
                return true;
            }
        }
        return false;
    }
}