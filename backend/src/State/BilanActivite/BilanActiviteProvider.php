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

namespace App\State\BilanActivite;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\BilanActivite;
use App\ApiResource\Telechargement;
use App\ApiResource\Utilisateur;
use App\Entity\Bilan;
use App\State\AbstractEntityProvider;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BilanActiviteProvider extends AbstractEntityProvider
{

    public function __construct(#[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')] ProviderInterface       $itemProvider,
                                #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')] ProviderInterface $collectionProvider,
                                private readonly IriConverterInterface                                                        $iriConverter)
    {
        parent::__construct($itemProvider, $collectionProvider);
    }

    protected function getResourceClass(): string
    {
        return BilanActivite::class;
    }

    protected function getEntityClass(): string
    {
        return Bilan::class;
    }

    /**
     * @param Bilan $entity
     * @return mixed
     * @throws Exception
     */
    public function transform($entity): mixed
    {
        $resource = new BilanActivite();
        $resource->id = $entity->getId();
        $resource->debut = $entity->getDebut();
        $resource->fin = $entity->getFin();
        $resource->dateDemande = $entity->getDateDemande();
        $resource->demandeur = $this->transformerService->transform(
            $entity->getDemandeur(), Utilisateur::class);
        $resource->dateGeneration = $entity->getDateGeneration();
        $resource->fichier = $this->transformerService->transform(
            $entity->getFichier(), Telechargement::class);

        $resource->gestionnaires = array_map(
            fn($iri) => $this->iriConverter->getResourceFromIri($iri),
            $entity->getParametre('gestionnaires') ?? []
        );
        $resource->composantes = array_map(
            fn($iri) => $this->iriConverter->getResourceFromIri($iri),
            $entity->getParametre('composantes') ?? []
        );
        $resource->formations = array_map(
            fn($iri) => $this->iriConverter->getResourceFromIri($iri),
            $entity->getParametre('formations') ?? []
        );
        $resource->profils = array_map(
            fn($iri) => $this->iriConverter->getResourceFromIri($iri),
            $entity->getParametre('profils') ?? []
        );


        return $resource;
    }

}