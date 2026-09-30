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
use App\State\Inscription\InscriptionProvider;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Get(
        uriTemplate: self::ITEM_URI,
        uriVariables: ['id'],
        openapi: false,
        security: "is_granted('" . self::VOIR_INSCRIPTION . "', object)",
        provider: InscriptionProvider::class,
        stateOptions: new Options(entityClass: \App\Entity\Inscription::class),
    ),
])]
final class Inscription
{
    public const string COLLECTION_URI = '/inscriptions';
    public const string ITEM_URI = self::COLLECTION_URI . '/{id}';

    public const string VOIR_INSCRIPTION = 'VOIR_INSCRIPTION';

    #[ApiProperty(identifier: true)]
    public int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id;
        }
    }

    #[Groups([
        Utilisateur::GROUP_OUT,
        Demande::GROUP_OUT,
        Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT,
        Amenagement::GROUP_OUT,
    ])]
    public Formation $formation {
        get {
            $prop = new ReflectionProperty(self::class, 'formation');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getFormation()) {
                $this->formation = new Formation($this->entity->getFormation());
            }
            return $this->formation;
        }
    }

    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public DateTimeInterface $debut {
        get {
            $prop = new ReflectionProperty(self::class, 'debut');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->debut = $this->entity->getDebut();
            }
            return $this->debut;
        }
    }
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public DateTimeInterface $fin {
        get {
            $prop = new ReflectionProperty(self::class, 'fin');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->fin = $this->entity->getFin();
            }
            return $this->fin;
        }
    }

    /**
     * code étape Apogée conservé pour exposer le cursus
     * d'inscription du bénéficiaire et permettre au front de regrouper
     * les inscriptions du même parcours.
     */
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public ?string $codeEtape {
        get {
            $prop = new ReflectionProperty(self::class, 'codeEtape');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->codeEtape = $this->entity->getCodeEtape();
            }
            return $this->codeEtape ?? null;
        }
    }

    /**
     * Niveau d'études de l'inscription (L1/L2/L3/M1/M2/D1-D3, BUT1…).
     *
     * Le niveau porté par la formation fait foi ; il n'est dérivé que s'il est
     * absent, à partir des données Apogée nationales (cycle du diplôme + année
     * dans le diplôme), avec repli sur le préfixe du code étape pour les
     * instances où celui-ci encode le niveau (L1INFO, M1ARTS…). null quand
     * aucune source ne permet de conclure (PASS, LAS, codes locaux).
     */
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public ?string $niveau {
        get {
            $prop = new ReflectionProperty(self::class, 'niveau');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                // Source unique : l'arbitrage entre le niveau de la formation et
                // la dérivation (résolveur + repli sur le code étape) vit sur l'entité.
                $this->niveau = $this->entity->getNiveau();
            }
            return $this->niveau ?? null;
        }
    }

    /**
     * code du cursus aménagé SISE (cod_sis_cur_amg). null hors
     * cursus aménagé.
     */
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public ?string $codeCursusAmenage {
        get {
            $prop = new ReflectionProperty(self::class, 'codeCursusAmenage');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->codeCursusAmenage = $this->entity->getCodeCursusAmenage();
            }
            return $this->codeCursusAmenage ?? null;
        }
    }

    /**
     * libellé du cursus aménagé (lib_cur_amg), affiché tel quel
     * sur la fiche bénéficiaire. null hors cursus aménagé.
     */
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public ?string $libelleCursusAmenage {
        get {
            $prop = new ReflectionProperty(self::class, 'libelleCursusAmenage');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->libelleCursusAmenage = $this->entity->getLibelleCursusAmenage();
            }
            return $this->libelleCursusAmenage ?? null;
        }
    }

    /**
     * Redoublement déduit à la volée du compteur natif du SI scolarité
     * (nbr_ins_etp, au-delà de un) via RedoublementCalculator, avec garde sur le
     * cursus aménagé. Jamais persisté.
     */
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public bool $redoublant {
        get {
            $prop = new ReflectionProperty(self::class, 'redoublant');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->redoublant = (new \App\Service\SiScol\RedoublementCalculator())->estRedoublant(
                    $this->entity->getNombreInscriptionsEtape(),
                    $this->entity->getCodeCursusAmenage(),
                );
            }
            return $this->redoublant ?? false;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\Inscription $entity = null,
    ) {}
}
