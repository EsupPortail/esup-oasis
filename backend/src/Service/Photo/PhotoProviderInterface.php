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

namespace App\Service\Photo;

use App\Entity\Utilisateur;

interface PhotoProviderInterface
{
    /**
     * @param Utilisateur $utilisateur
     * @return mixed
     * @throws PhotoIndisponibleException
     */
    public function getPhotoUtilisateur(Utilisateur $utilisateur);
}