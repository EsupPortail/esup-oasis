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

namespace App\State\Formation;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Composante;
use App\ApiResource\Formation;
use App\Entity\Utilisateur;
use App\State\AbstractEntityProvider;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FormationProvider extends AbstractEntityProvider
{

    public function __construct(private readonly Security                                                                              $security,
                                #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')] readonly ProviderInterface       $itemProvider,
                                #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')] readonly ProviderInterface $collectionProvider,
    )
    {
        parent::__construct($itemProvider, $collectionProvider);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            /**
             * @var Utilisateur $user
             */
            $user = $this->security->getUser();
            if (!($user?->getComposantes()->isEmpty() ?? true)) {
                //est-ce qu'il y a déjà un filtre sur composante?
                $irisAccessibles = array_map(fn($cmp) => Composante::COLLECTION_URI . '/' . $cmp->getid(), $user->getComposantes()->toArray());
                if (!array_key_exists('composante', $context['filters'] ?? [])) {
                    $context['filters']['composante'] = $irisAccessibles;
                } else {
                    //on ne conserve que les composantes, accessibles
                    $context['filters']['composante'] = array_filter(
                        is_array($context['filters']['composante']) ? $context['filters']['composante'] : [$context['filters']['composante']],
                        fn($iri) => in_array($iri, $irisAccessibles)
                    );
                    if (empty($context['filters']['composante'])) {
                        $context['filters']['composante'] = $irisAccessibles;
                    }
                }

            }
        }

        return parent::provide($operation, $uriVariables, $context);
    }

    protected function getResourceClass(): string
    {
        return Formation::class;
    }

    protected function getEntityClass(): string
    {
        return \App\Entity\Formation::class;
    }

    /**
     * @param \App\Entity\Formation $entity
     * @return mixed
     * @throws Exception
     */
    public function transform($entity): Formation
    {
        $resource = new Formation();
        $resource->id = $entity->getId();
        $resource->libelle = $entity->getLibelle();
        $resource->composante = $this->transformerService->transform($entity->getComposante(), Composante::class);
        $resource->codeExterne = $entity->getCodeExterne();
        $resource->discipline = $entity->getDiscipline();
        $resource->diplome = $entity->getDiplome();
        $resource->niveau = $entity->getNiveau();

        return $resource;
    }
}