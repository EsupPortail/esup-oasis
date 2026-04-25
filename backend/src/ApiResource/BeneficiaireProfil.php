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

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Entity\Beneficiaire;
use App\State\BeneficiaireProfil\BeneficiaireProfilProcessor;
use App\State\BeneficiaireProfil\BeneficiaireProfilProvider;
use App\Validator\BeneficiaireDifferentGestionnaireContraint;
use App\Validator\BeneficiaireSupprimableConstraint;
use App\Validator\ProfilAvecTypologieConstraint;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(uriTemplate: self::ITEM_URI, uriVariables: ['uid', 'id']),
        new Patch(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['uid', 'id'],
            security: "is_granted('" . self::VOIR_PROFILS . "')",
            validationContext: ['groups' => [self::GROUP_VALIDATION_IN]],
        ),
        new Post(
            uriTemplate: self::COLLECTION_URI,
            uriVariables: ['uid'],
            security: "is_granted('" . self::VOIR_PROFILS . "')",
            validationContext: ['groups' => [self::GROUP_VALIDATION_IN]],
            read: false,
            map: false,
        ),
        new Delete(
            uriTemplate: self::ITEM_URI,
            uriVariables: ['uid', 'id'],
            security: "is_granted('" . self::VOIR_PROFILS . "')",
            validationContext: ['groups' => [self::GROUP_VALIDATION_DELETE]],
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_OUT]],
    denormalizationContext: ['groups' => [self::GROUP_IN]],
    openapi: new Operation(tags: ['Utilisateurs']),
    order: ['debut' => 'DESC '],
    security: "is_granted('ROLE_PLANIFICATEUR')",
    provider: BeneficiaireProfilProvider::class,
    processor: BeneficiaireProfilProcessor::class,
    stateOptions: new Options(entityClass: Beneficiaire::class),
)]
#[ApiFilter(SearchFilter::class, properties: ['profil'])]
#[ProfilAvecTypologieConstraint(groups: [self::GROUP_VALIDATION_IN])]
#[BeneficiaireDifferentGestionnaireContraint(groups: [self::GROUP_VALIDATION_IN])]
#[BeneficiaireSupprimableConstraint(groups: [self::GROUP_VALIDATION_DELETE])]
final class BeneficiaireProfil
{
    public const string COLLECTION_URI = '/utilisateurs/{uid}/profils';
    public const string ITEM_URI = '/utilisateurs/{uid}/profils/{id}';

    public const string GROUP_OUT = 'beneficiaires_profils:out';
    public const string GROUP_IN = 'beneficiaires_profils:in';
    public const string GROUP_VALIDATION_DELETE = 'beneficiaires_profils:validation:delete';
    public const string GROUP_VALIDATION_IN = 'beneficiaires_profils:validation:in';
    public const string VOIR_PROFILS = 'VOIR_PROFILS';

    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_OUT])]
    public ?int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id ?? null;
        }
    }

    //Copie de l'UID utilisateur. Non présenté à l'extérieur, juste utile pour uriVariables
    public ?string $uid {
        get {
            $prop = new ReflectionProperty(self::class, 'uid');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getUtilisateur()) {
                $this->uid = $this->entity->getUtilisateur()->getUid();
            }
            return $this->uid ?? null;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[ApiProperty(security: "is_granted('" . self::VOIR_PROFILS . "')")]
    public ProfilBeneficiaire $profil {
        get {
            $prop = new ReflectionProperty(self::class, 'profil');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getProfil()) {
                $this->profil = new ProfilBeneficiaire($this->entity->getProfil());
            }
            if (!$prop->isInitialized($this)) {
                $this->profil = new ProfilBeneficiaire();
                $this->profil->id = \App\Entity\ProfilBeneficiaire::A_DETERMINER;
            }
            return $this->profil;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank(groups: [self::GROUP_VALIDATION_IN])]
    public DateTimeInterface $debut {
        get {
            $prop = new ReflectionProperty(self::class, 'debut');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->debut = $this->entity->getDebut();
            }
            return $this->debut;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public ?DateTimeInterface $fin {
        get {
            $prop = new ReflectionProperty(self::class, 'fin');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->fin = $this->entity->getFin();
            }
            return $this->fin ?? null;
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\NotBlank(groups: [self::GROUP_VALIDATION_IN])]
    public Utilisateur $gestionnaire {
        get {
            $prop = new ReflectionProperty(self::class, 'gestionnaire');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->gestionnaire = new Utilisateur($this->entity->getGestionnaire());
            }
            return $this->gestionnaire;
        }
    }

    /**
     * @var TypologieHandicap[]
     */
    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    #[Assert\All([new Assert\Type(TypologieHandicap::class)], groups: [self::GROUP_VALIDATION_IN])]
    #[ApiProperty(security: "is_granted('" . self::VOIR_PROFILS . "')")]
    public array $typologies {
        get {
            $prop = new ReflectionProperty(self::class, 'typologies');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->typologies = array_map(
                    fn($t) => new TypologieHandicap($t),
                    $this->entity->getTypologies()->toArray(),
                );
            }
            return $this->typologies ?? [];
        }
    }

    #[Groups([self::GROUP_OUT, self::GROUP_IN])]
    public bool $avecAccompagnement {
        get {
            $prop = new ReflectionProperty(self::class, 'avecAccompagnement');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->avecAccompagnement = $this->entity->isAvecAccompagnement();
            }
            return $this->avecAccompagnement ?? true;
        }
    }

    public function __construct(
        private readonly ?Beneficiaire $entity = null,
    ) {}
}
