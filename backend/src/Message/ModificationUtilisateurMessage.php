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

namespace App\Message;


use App\Entity\Utilisateur;

readonly class ModificationUtilisateurMessage
{
    private string $uid;

    public function __construct(Utilisateur $utilisateur)
    {
        $this->uid = $utilisateur->getUid();
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }
}