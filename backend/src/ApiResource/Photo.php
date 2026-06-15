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

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\Photo\PhotoProvider;

#[ApiResource(
    operations: [
        new Get(uriTemplate: self::ITEM_URI, formats: ['jpeg' => 'image/jpeg'], uriVariables: ['uid']),
    ],
    openapi: new Operation(tags: ['Utilisateurs']),
    security: "is_granted('VOIR_PHOTO', object)",
    provider: PhotoProvider::class,
)]
class Photo
{
    public const string ITEM_URI = '/utilisateurs/{uid}/photo';
    public const string VOIR_PHOTO = 'VOIR_PHOTO';

    #[ApiProperty(identifier: true)]
    public string $uid;

    public string $data;
}
