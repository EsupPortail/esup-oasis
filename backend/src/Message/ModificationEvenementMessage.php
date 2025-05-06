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

use App\Entity\Evenement;
use DateTimeInterface;

class ModificationEvenementMessage
{

    private int $id;

    public function __construct(readonly Evenement $evenement, private readonly ?DateTimeInterface $dateOrigine = null, private readonly bool $creation = false)
    {
        $this->id = $evenement->getId();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isCreation(): bool
    {
        return $this->creation;
    }

    public function getDateOrigine(): ?DateTimeInterface
    {
        return $this->dateOrigine;
    }

}