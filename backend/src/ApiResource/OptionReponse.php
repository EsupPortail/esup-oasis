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
use App\State\OptionReponse\OptionReponseProvider;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ApiResource(operations: [
    new Get(
        uriTemplate: self::ITEM_URI,
        uriVariables: ['id', 'questionId'],
        openapi: false,
        provider: OptionReponseProvider::class,
    ),
], stateOptions: new Options(entityClass: \App\Entity\OptionReponse::class))]
class OptionReponse
{
    public const string ITEM_URI = '/questions/{questionId}/options/{id}';

    #[ApiProperty(identifier: true)]
    #[Groups([Question::GROUP_OUT, Reponse::GROUP_OUT, Demande::GROUP_OUT])]
    public ?int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }

    #[Ignore]
    public int $questionId {
        get {
            $prop = new ReflectionProperty(self::class, 'questionId');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->questionId = $this->entity->getQuestion()->getId();
            }
            return $this->questionId ?? null;
        }
    }

    #[Groups([Question::GROUP_OUT, Reponse::GROUP_OUT, Demande::GROUP_OUT])]
    public string $libelle {
        get {
            $prop = new ReflectionProperty(self::class, 'libelle');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelle = $this->entity->getLibelle();
            }
            return $this->libelle;
        }
    }

    #[Groups([Question::GROUP_OUT, Reponse::GROUP_OUT])]
    public array $questionsLiees {
        get {
            $prop = new ReflectionProperty(self::class, 'questionsLiees');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->questionsLiees = array_map(
                    fn($entity) => new Question($entity),
                    $this->entity->getQuestionsLiees()->toArray(),
                );
            }
            return $this->questionsLiees ?? [];
        }
    }

    public function __construct(
        private readonly ?\App\Entity\OptionReponse $entity = null,
    ) {}

    public static function fromReference(mixed $item): self
    {
        //toutes les classes en entrée ont id et libellé, pas de support des questions liées
        $resource = new self();
        $resource->id = $item->getId();
        $resource->libelle = $item->getLibelle();
        $resource->questionsLiees = [];

        return $resource;
    }
}
