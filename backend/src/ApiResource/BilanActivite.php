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

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Bilan;
use App\Filter\NestedUtilisateurFilter;
use App\State\BilanActivite\BilanActiviteProcessor;
use App\State\BilanActivite\BilanActiviteProvider;
use DateTimeInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI,
            security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
        ),
        new Get(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
        ),
        new Delete(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['id'],
            security: "object.getUid() == user.getUid()",
        ),
    ],
    cacheHeaders: [
        'public' => false,
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Suivis'], description: 'Bilan activite'),
    provider: BilanActiviteProvider::class,
    processor: BilanActiviteProcessor::class,
    stateOptions: new Options(entityClass: Bilan::class)
)]
#[ApiFilter(OrderFilter::class, properties: ['dateDemande'])]
#[ApiFilter(NestedUtilisateurFilter::class, properties: ['demandeur' => 'demandeur'])]
class BilanActivite
{
    public const string COLLECTION_URI = '/suivis/activite';
    public const string ITEM_URI = '/suivis/activite/{id}';
    public const string GROUP_IN = 'bilan-activite:in';
    public const string GROUP_OUT = 'bilan-activite:out';

    #[Groups([self::GROUP_OUT])]
    public ?int $id = null;

    #[Groups([self::GROUP_OUT])]
    public Utilisateur $demandeur;

    #[Assert\NotNull]
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public DateTimeInterface $debut;
    #[Assert\NotNull]
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public DateTimeInterface $fin;

    #[Groups([self::GROUP_OUT])]
    public DateTimeInterface $dateDemande;

    #[Groups([self::GROUP_OUT])]
    public ?DateTimeInterface $dateGeneration = null;

    #[Groups([self::GROUP_OUT])]
    public ?Telechargement $fichier = null;

    /**
     * @var Utilisateur[]
     */
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public array $gestionnaires = [];

    /**
     * @var ProfilBeneficiaire[]
     */
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public array $profils = [];

    /**
     * @var Composante[]
     */
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public array $composantes = [];

    /**
     * @var Formation[]
     */
    #[Groups([self::GROUP_IN, self::GROUP_OUT])]
    public array $formations = [];

    public function getuid()
    {
        return $this->demandeur->uid;
    }
}