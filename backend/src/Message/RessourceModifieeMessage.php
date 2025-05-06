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

class RessourceModifieeMessage
{
    public function __construct(private $resource)
    {
    }

    /**
     * @return mixed
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }
}