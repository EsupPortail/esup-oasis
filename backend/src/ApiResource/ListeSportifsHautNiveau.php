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

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\SportfHautNiveau\SportifHautNiveauUploadProcessor;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Put(
            uriTemplate: SportifHautNiveau::COLLECTION_URI,
            denormalizationContext: ['groups' => [self::GROUP_IN]],
            security: "is_granted('ROLE_ADMIN')",
            processor: SportifHautNiveauUploadProcessor::class,
            extraProperties: ['standard_put' => true],
            allowCreate: true
        ),
        new Get(
            uriTemplate: '/liste_sportifs_haut_niveau'
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    openapi: new Operation(tags: ['Referentiel']),
    security: "is_granted('ROLE_ADMIN_TECHNIQUE')",
)]
class ListeSportifsHautNiveau
{
    public const string GROUP_OUT = 'sportif_haut_niveau:out';
    public const string GROUP_IN = 'sportif_haut_niveau:post';


    #[Ignore] public int $id = 1;

    /**
     * @var SportifHautNiveau[]
     */
    #[Groups([self::GROUP_OUT])]
    public array $sportifs;

    #[Groups([self::GROUP_IN])]
    #[Assert\NotNull]
    public ?Telechargement $telechargement = null;
}