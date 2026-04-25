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

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\Demande\ModificationEtatDemandeProvider;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: self::COLLECTION_URI,
            uriVariables: [
                'demandeId' => new Link(fromProperty: 'id', toProperty: 'demande', fromClass: Demande::class),
            ],
            map: false,
        ),
        new Get(uriTemplate: self::ITEM_URI, uriVariables: [
            'demandeId' => new Link(fromProperty: 'id', toProperty: 'demande', fromClass: Demande::class),
            'id',
        ]),
    ],
    openapi: new Operation(tags: ['Demandes']),
    provider: ModificationEtatDemandeProvider::class,
    stateOptions: new Options(entityClass: \App\Entity\ModificationEtatDemande::class),
)]
#[ApiFilter(OrderFilter::class, properties: ['dateModification', 'id'])]
#[ApiFilter(DateFilter::class, properties: ['dateModification'])]
#[ApiFilter(SearchFilter::class, properties: ['demande'])]
class ModificationEtatDemande
{
    public const string COLLECTION_URI = '/demandes/{demandeId}/modifications';
    public const string ITEM_URI = '/demandes/{demandeId}/modifications/{id}';

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

    public Demande $demande {
        get {
            $prop = new ReflectionProperty(self::class, 'demande');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->demande = new Demande($this->entity->getDemande());
            }
            return $this->demande ?? null;
        }
    }

    public EtatDemande $etat {
        get {
            $prop = new ReflectionProperty(self::class, 'etat');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->etat = new EtatDemande($this->entity->getEtat());
            }
            return $this->etat ?? null;
        }
    }

    public EtatDemande $etatPrecedent {
        get {
            $prop = new ReflectionProperty(self::class, 'etatPrecedent');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->etatPrecedent = new EtatDemande($this->entity->getEtatPrecedent());
            }
            return $this->etatPrecedent;
        }
    }

    public Utilisateur $utilisateurModification {
        get {
            $prop = new ReflectionProperty(self::class, 'utilisateurModification');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->utilisateurModification = new Utilisateur($this->entity->getUtilisateur());
            }
            return $this->utilisateurModification;
        }
    }

    public ?ProfilBeneficiaire $profil {
        get {
            $prop = new ReflectionProperty(self::class, 'profil');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getProfil() !== null) {
                $this->profil = new ProfilBeneficiaire($this->entity->getProfil());
            }
            return $this->profil ?? null;
        }
    }

    public ?string $commentaire {
        get {
            $prop = new ReflectionProperty(self::class, 'commentaire');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->commentaire = $this->entity->getCommentaire();
            }
            return $this->commentaire ?? null;
        }
    }

    public ?DateTimeInterface $dateModification {
        get {
            $prop = new ReflectionProperty(self::class, 'dateModification');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->dateModification = $this->entity->getDateModification();
            }
            return $this->dateModification ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\ModificationEtatDemande $entity = null,
    ) {}
}
