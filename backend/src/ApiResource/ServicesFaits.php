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

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Filter\ServicesFaitsPeriodeOrderFilter;
use App\State\PeriodeRH\IntervenantServicesFaitsProvider;
use App\State\PeriodeRH\ServicesFaitsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/intervenants/{uid}/services_faits',
            uriVariables: ['uid'],
            security: "is_granted('ROLE_ADMIN') or request.attributes.get('uid') == user.getUid()",
            provider: IntervenantServicesFaitsProvider::class,
        ),
        new Get(
            uriTemplate: '/intervenants/{uid}/services_faits/{id}', //id = id de la période RH
            uriVariables: ['uid', 'id'],
            security: "is_granted('ROLE_ADMIN') or object.uid == user.getUid()",
            provider: IntervenantServicesFaitsProvider::class,
        ),
        new Get(
            uriTemplate: '/periodes/{id}/services_faits',
            formats: ['jsonld', 'customcsv' => 'text/csv', 'pdf' => 'application/pdf'],
            uriVariables: ['id'],
            security: "is_granted('ROLE_GESTIONNAIRE') and object.periode.envoyee == true",
            provider: ServicesFaitsProvider::class,
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
)]
#[ApiFilter(ServicesFaitsPeriodeOrderFilter::class)]
class ServicesFaits
{
    public const string GROUP_OUT = 'services_faits:out';

    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_OUT])]
    public int $id; //copie de periode.id

    //copie de l'uid de l'intervenant si besoin, pour génération de l'IRI
    public ?string $uid = null;

    #[Groups([self::GROUP_OUT])]
    public PeriodeRH $periode;

    #[Groups([self::GROUP_OUT])]
    public string $structure;

    /**
     * @var LigneServiceFait[]
     */
    #[Groups([self::GROUP_OUT])]
    public array $lignes;
}
