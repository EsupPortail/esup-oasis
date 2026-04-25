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
use ApiPlatform\OpenApi\Model\Operation;
use App\State\Question\QuestionProvider;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(openapi: new Operation(tags: ['Demandes']), provider: QuestionProvider::class),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    stateOptions: new Options(entityClass: \App\Entity\Question::class),
)]
class Question
{
    public const string GROUP_OUT = 'question:out';
    public const string COLLECTION_URI = '/questions';
    public const string ITEM_URI = '/questions/{id}';

    #[Groups([self::GROUP_OUT])]
    #[ApiProperty(identifier: true)]
    public ?int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public string $libelle {
        get {
            $prop = new ReflectionProperty(self::class, 'libelle');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelle = $this->entity->getLibelle();
            }
            return $this->libelle;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?string $aide {
        get {
            $prop = new ReflectionProperty(self::class, 'aide');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->aide = $this->entity->getAide();
            }
            return $this->aide ?? null;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public string $typeReponse {
        get {
            $prop = new ReflectionProperty(self::class, 'typeReponse');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->typeReponse = $this->entity->getTypeReponse();
            }
            return $this->typeReponse;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public bool $obligatoire {
        get {
            $prop = new ReflectionProperty(self::class, 'obligatoire');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->obligatoire = $this->entity->isObligatoire();
            }
            return $this->obligatoire ?? false;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public bool $choixMultiple {
        get {
            $prop = new ReflectionProperty(self::class, 'choixMultiple');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->choixMultiple = $this->entity->isChoixMultiple();
            }
            return $this->choixMultiple ?? false;
        }
    }

    /**
     * @var OptionReponse[]
     */
    #[Groups([self::GROUP_OUT])]
    public array $optionsReponses {
        get {
            $prop = new ReflectionProperty(self::class, 'optionsReponses');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->optionsReponses = array_map(
                    fn($option) => new OptionReponse($option),
                    $this->entity->getOptionsReponse()->toArray(),
                );
            }
            return $this->optionsReponses ?? [];
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?string $tableOptions {
        get {
            $prop = new ReflectionProperty(self::class, 'tableOptions');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->tableOptions = $this->entity->getTableOptions();
            }
            return $this->tableOptions ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\Question $entity = null,
    ) {}
}
