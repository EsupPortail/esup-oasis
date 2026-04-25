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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use App\State\DecisionAmenagementExamens\DecisionAmenagementExamensProcessor;
use App\State\DecisionAmenagementExamens\DecisionAmenagementExamensProvider;
use App\Validator\EtatDecisionValideConstraint;
use ReflectionProperty;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: self::ITEM_URI,
            outputFormats: ['jsonld', 'pdf' => 'application/pdf'],
            uriVariables: ['uid', 'annee'],
        ),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['uid', 'annee'],
            securityPostDenormalize: "is_granted('" . self::MODIFIER_DECISION . "', object)",
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    security: "is_granted('" . \App\Entity\Utilisateur::ROLE_GESTIONNAIRE . "')",
    provider: DecisionAmenagementExamensProvider::class,
    processor: DecisionAmenagementExamensProcessor::class,
    stateOptions: new Options(entityClass: \App\Entity\DecisionAmenagementExamens::class),
)]
#[Map(target: \App\Entity\DecisionAmenagementExamens::class)]
class DecisionAmenagementExamens
{
    public const string ITEM_URI = '/utilisateurs/{uid}/decisions/{annee}';
    public const string MODIFIER_DECISION = 'MODIFIER_DECISION';

    public const string GROUP_IN = 'decision:in';
    public const string GROUP_OUT = 'decision:out';

    #[Ignore]
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
    public string $uid {
        get {
            $prop = new ReflectionProperty(self::class, 'uid');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getBeneficiaire()) {
                $this->uid = $this->entity->getBeneficiaire()->getUid();
            }
            return $this->uid;
        }
    }
    #[Ignore]
    public int $annee {
        get {
            $prop = new ReflectionProperty(self::class, 'annee');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getDebut()) {
                $this->annee = (int) $this->entity->getDebut()->format('Y');
            }
            return $this->annee;
        }
    }

    #[Groups([Utilisateur::GROUP_OUT, self::GROUP_OUT, self::GROUP_IN])]
    #[EtatDecisionValideConstraint]
    public string $etat {
        get {
            $prop = new ReflectionProperty(self::class, 'etat');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->etat = $this->entity->getEtat() ?? '';
            }
            return $this->etat;
        }
    }

    #[Groups([self::GROUP_OUT])]
    public ?string $urlContenu {
        get {
            $prop = new ReflectionProperty(self::class, 'urlContenu');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getFichier()) {
                $this->urlContenu = '/fichiers/' . $this->entity->getFichier()->getId();
            }
            return $this->urlContenu ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\DecisionAmenagementExamens $entity = null,
    ) {}
}
