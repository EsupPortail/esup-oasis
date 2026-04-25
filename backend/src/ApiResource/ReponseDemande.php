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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [new Get()], openapi: false)]
class ReponseDemande
{
    public ?int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }

    #[Groups([Demande::GROUP_OUT])]
    public ?string $commentaire {
        get {
            $prop = new ReflectionProperty(self::class, 'commentaire');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->commentaire = $this->entity->getCommentaire();
            }
            return $this->commentaire ?? null;
        }
    }

    /**
     * @var OptionReponse[]
     */
    #[Groups([Demande::GROUP_OUT])]
    public ?array $optionsReponses {
        get {
            $prop = new ReflectionProperty(self::class, 'optionsReponses');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->optionsReponses = array_map(fn($option) => $option instanceof \App\Entity\OptionReponse
                    ? new OptionReponse($option)
                    : OptionReponse::fromReference($option), $this->entity->getOptionsChoisiesTousTypes());
                //on ajoute l'id de question qui manque...
                array_walk(
                    $this->optionsReponses,
                    fn($option) => $option->questionId = $this->entity->getQuestion()->getId(),
                );
            }
            return $this->optionsReponses ?? [];
        }
    }

    /**
     * @var Telechargement[]
     */
    #[Groups([Demande::GROUP_OUT])]
    public ?array $piecesJustificatives {
        get {
            $prop = new ReflectionProperty(self::class, 'piecesJustificatives');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->piecesJustificatives = array_map(
                    fn($pj) => new Telechargement($pj),
                    $this->entity->getPiecesJustificatives()->toArray(),
                );
            }
            return $this->piecesJustificatives ?? [];
        }
    }

    public function __construct(
        private ?\App\Entity\Reponse $entity = null,
    ) {}
}
