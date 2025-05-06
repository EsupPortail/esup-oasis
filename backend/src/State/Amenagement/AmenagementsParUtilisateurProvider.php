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

namespace App\State\Amenagement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Composante;
use App\ApiResource\Inscription;
use App\ApiResource\Tag;
use App\ApiResource\TypeAmenagement;
use App\ApiResource\Utilisateur;
use App\Entity\Amenagement;
use App\Entity\Beneficiaire;
use App\Filter\BeneficiaireAvecAmenagementEnCoursFilter;
use App\State\AbstractEntityProvider;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AmenagementsParUtilisateurProvider extends AbstractEntityProvider
{
    use ClockAwareTrait;

    public function __construct(
        private readonly Security                                                                     $security,
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')] ProviderInterface       $itemProvider,
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')] ProviderInterface $collectionProvider)
    {
        parent::__construct($itemProvider, $collectionProvider);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $context['filters'][BeneficiaireAvecAmenagementEnCoursFilter::PROPERTY] = true;

        // les renforts ne voient que les aménagements d'aide humaine !
        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_RENFORT)) {
            $context['filters']['examens'] = false;
            $context['filters']['pedagogique'] = false;
        }

        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_REFERENT_COMPOSANTE)) {
            /**
             * @var \App\Entity\Utilisateur $user
             */
            $user = $this->security->getUser();

            //todo: petit trou ici : si composante fournie, vérifier qu'elle fait partie des composantes autorisées...
            if (!array_key_exists('composante', $context['filters'] ?? [])) {
                $context['filters']['composante'] = array_map(
                    fn($cmp) => Composante::COLLECTION_URI . '/' . $cmp->getId(),
                    $user->getComposantes()->toArray()
                );
            }

        }

        return parent::provide($operation, $uriVariables, $context);
    }

    protected function getComposanteIri(\App\Entity\Composante $composante): string
    {
        return Composante::COLLECTION_URI . '/' . $composante->getId();
    }

    protected function getResourceClass(): string
    {
        return Utilisateur::class;
    }

    protected function getEntityClass(): string
    {
        return \App\Entity\Utilisateur::class;
    }

    /**
     * @param \App\Entity\Utilisateur $entity
     * @return Utilisateur
     * @throws Exception
     */
    public function transform($entity): mixed
    {
        /**
         * On veut juste quelques infos pertinentes, on ne renseigne pas le reste dans le vide
         */
        $resource = new Utilisateur();
        $resource->uid = $entity->getUid();
        $resource->nom = $entity->getNom();
        $resource->prenom = $entity->getPrenom();
        $resource->email = $entity->getEmail();
        $resource->numeroEtudiant = $entity->getNumeroEtudiant();

        $amenagementsVisibles = $entity->getAmenagementsActifs();

        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_RENFORT)) {
            //on ne montre que les aides humaines
            $amenagementsVisibles = array_values(array_filter(
                    $amenagementsVisibles,
                    fn(Amenagement $amenagement) => $amenagement->getType()->isAideHumaine()
                )
            );
        }

        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_REFERENT_COMPOSANTE)) {
            //on ne montre que les aménagements d'examen et pédagogiques
            $amenagementsVisibles = array_values(array_filter(
                    $amenagementsVisibles,
                    fn(Amenagement $amenagement) => $amenagement->getType()->isExamens() || $amenagement->getType()->isPedagogique()
                )
            );
        }

        $resource->amenagements = array_values(array_map(
                function (Amenagement $amenagement) {
                    $resource = new \App\ApiResource\Amenagement();
                    $resource->id = $amenagement->getId();
                    $resource->uid = $amenagement->getBeneficiaires()->current()->getUtilisateur()->getUid();
                    $resource->typeAmenagement = $this->transformerService->transform($amenagement->getType(), TypeAmenagement::class);
                    $resource->commentaire = $amenagement->getCommentaire();
                    return $resource;
                },
                $amenagementsVisibles
            )
        );

        $derniereInscription = $entity->getDerniereInscription();
        $resource->inscriptions = match ($derniereInscription) {
            null => [],
            default => [$this->transformerService->transform($derniereInscription, Inscription::class)]
        };

        //Ajouts infos manquantes pour export
        $resource->etatAvisEse = $entity->getEtatAvisEse();

        $resource->tags = array_values(array_unique(array_map(
            fn($tag) => $this->transformerService->transform($tag, Tag::class),
            array_reduce(
                $entity->getBeneficiairesActifs(),
                fn(array $carry, Beneficiaire $beneficiaire) => [...$carry, ...$beneficiaire->getTags()],
                []
            )
        ), SORT_REGULAR));


        return $resource;
    }

    public function registerTransformations(): void
    {
    }
}