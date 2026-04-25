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

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Controller\EnvoiPjAction;
use App\Entity\Fichier;
use App\State\PieceJustificative\TelechargementProcessor;
use App\State\PieceJustificative\TelechargementProvider;
use App\Validator\NoVirusConstraint;
use ArrayObject;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: self::COLLECTION_URI,
            controller: EnvoiPjAction::class,
            openapi: new Operation(requestBody: new RequestBody(content: new ArrayObject([
                'multipart/form-data' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'file' => [
                                'type' => 'string',
                                'format' => 'binary',
                            ],
                        ],
                    ],
                ],
            ]))),
            security: "is_granted('ROLE_GESTIONNAIRE') or is_granted('ROLE_RENFORT_DEMANDES') or is_granted('ROLE_DEMANDEUR')",
            deserialize: false,
            map: false,
        ),
        new Get(uriTemplate: self::ITEM_URI, security: "is_granted('" . Fichier::VOIR_FICHIER . "', object)"),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    provider: TelechargementProvider::class,
    processor: TelechargementProcessor::class,
    stateOptions: new Options(entityClass: Fichier::class),
)]
#[NoVirusConstraint]
class Telechargement
{
    public const string COLLECTION_URI = '/telechargements';
    public const string ITEM_URI = '/telechargements/{id}';
    public const string GROUP_IN = 'telechargement:in';
    public const string GROUP_OUT = 'telechargement:out';

    #[ApiProperty(identifier: true)]
    public ?string $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }

    #[Groups(self::GROUP_OUT)]
    public Utilisateur $proprietaire {
        get {
            $prop = new ReflectionProperty(self::class, 'proprietaire');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getProprietaire() !== null) {
                $this->proprietaire = new Utilisateur($this->entity->getProprietaire());
            }
            return $this->proprietaire;
        }
    }

    #[Groups([self::GROUP_IN])]
    public UploadedFile $file;

    #[Groups([self::GROUP_OUT])]
    public string $nom {
        get {
            $prop = new ReflectionProperty(self::class, 'nom');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->nom = $this->entity->getNom();
            }
            return $this->nom;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public string $typeMime {
        get {
            $prop = new ReflectionProperty(self::class, 'typeMime');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->typeMime = $this->entity->getTypeMime();
            }
            return $this->typeMime ?? null;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public string $urlContenu {
        get {
            $prop = new ReflectionProperty(self::class, 'urlContenu');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->urlContenu = '/fichiers/' . $this->id; //en dur, c'est moche
            }
            return $this->urlContenu ?? null;
        }
    }

    public function __construct(
        private readonly ?Fichier $entity = null,
    ) {}
}
