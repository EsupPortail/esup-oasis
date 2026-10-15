<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

declare(strict_types=1);

namespace App\ApiResource;

use Symfony\Component\Serializer\Attribute\Groups;

readonly class InfoComplementaire
{
    function __construct(
        #[Groups(Utilisateur::GROUP_OUT)]
        public string $libelle,
        #[Groups(Utilisateur::GROUP_OUT)]
        public string $valeur,
    ) {}
}
