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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\QuestionEtapeDemande;
use App\State\EtapeDemande\EtapeDemandeProvider;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['id'], openapi: new Operation(tags: ['Demandes'])),
        new GetCollection(uriTemplate: self::COLLECTION_URI, openapi: new Operation(tags: ['Demandes'])),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    provider: EtapeDemandeProvider::class,
    stateOptions: new Options(entityClass: \App\Entity\EtapeDemande::class),
)]
class EtapeDemande
{
    public const string COLLECTION_URI = '/etapes_demandes';
    public const string ITEM_URI = '/etapes_demandes/{id}';
    public const string GROUP_IN = 'etape_demande:in';
    public const string GROUP_OUT = 'etape_demande:out';

    #[ApiProperty(identifier: true)]
    #[Groups([TypeDemande::GROUP_OUT, Demande::GROUP_OUT, self::GROUP_OUT])]
    public ?int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }

    #[Groups([TypeDemande::GROUP_OUT, Demande::GROUP_OUT, self::GROUP_OUT, self::GROUP_IN])]
    public string $libelle {
        get {
            $prop = new ReflectionProperty(self::class, 'libelle');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelle = $this->entity->getLibelle();
            }
            return $this->libelle;
        }
    }

    #[Groups([TypeDemande::GROUP_OUT, Demande::GROUP_OUT, self::GROUP_OUT, self::GROUP_IN])]
    public int $ordre {
        get {
            $prop = new ReflectionProperty(self::class, 'ordre');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->ordre = $this->entity->getOrdre();
            }
            return $this->ordre;
        }
    }

    /**
     * @var Question[]
     */
    #[Groups([TypeDemande::GROUP_OUT, self::GROUP_OUT, self::GROUP_IN])]
    public array $questions {
        get {
            $prop = new ReflectionProperty(self::class, 'questions');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->questions = array_map(
                    callback: fn(QuestionEtapeDemande $questionEtapeDemande) => new Question(
                        $questionEtapeDemande->getQuestion(),
                    ),
                    array: $this->entity->getQuestionsEtape()->toArray(),
                );
            }
            return $this->questions ?? [];
        }
    }

    public function __construct(
        private readonly ?\App\Entity\EtapeDemande $entity = null,
    ) {}
}
