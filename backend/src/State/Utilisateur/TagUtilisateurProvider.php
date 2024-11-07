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

namespace App\State\Utilisateur;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Tag;
use App\ApiResource\TagUtilisateur;
use App\ApiResource\Utilisateur;
use App\State\TransformerService;
use Exception;
use Override;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TagUtilisateurProvider implements ProviderInterface
{

    use ClockAwareTrait;

    public function __construct(private readonly UtilisateurManager $utilisateurManager,
                                private readonly TransformerService $transformerService)
    {
    }

    /**
     * @param Operation $operation
     * @param array     $uriVariables
     * @param array     $context
     * @return object|array|object[]|null
     * @throws Exception
     */
    #[Override] public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        try {
            $utilisateur = $this->utilisateurManager->parUid($uriVariables['uid']);
        } catch (Exception) {
            throw new NotFoundHttpException("Utilisateur inconnu");
        }
        $tagsActifs = $utilisateur->getTagsActifs();

        if ($operation instanceof GetCollection) {
            //on récupère tous les tags sur les bénéficiaires actifs
            return array_map(
                fn($tag) => $this->transform($utilisateur, $tag),
                $tagsActifs
            );
        }
        //accès direct à un tag
        $matches = array_filter($tagsActifs, fn(\App\Entity\Tag $tag) => $tag->getId() === $uriVariables['id']);
        if (count($matches) === 0) {
            throw new NotFoundHttpException("Tag non présent");
        }
        return $this->transform($utilisateur, current($matches));
    }

    /**
     * @param \App\Entity\Utilisateur $utilisateur
     * @param \App\Entity\Tag         $tag
     * @return TagUtilisateur
     * @throws Exception
     */
    public function transform(\App\Entity\Utilisateur $utilisateur, \App\Entity\Tag $tag): TagUtilisateur
    {
        return new TagUtilisateur(
            $this->transformerService->transform($utilisateur, Utilisateur::class),
            $this->transformerService->transform($tag, Tag::class)
        );
    }
}