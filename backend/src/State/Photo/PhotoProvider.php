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

namespace App\State\Photo;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Photo;
use App\Service\Photo\PhotoIndisponibleException;
use App\Service\Photo\PhotoProviderInterface;
use App\State\Utilisateur\UtilisateurManager;
use Override;

readonly class PhotoProvider implements ProviderInterface
{

    public function __construct(private PhotoProviderInterface $photoProvider,
                                private UtilisateurManager     $utilisateurManager)
    {

    }

    #[Override] public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        //GET sur un item seulement, uid de l'utilisateur dans $uriVariables
        $utilisateur = $this->utilisateurManager->parUid($uriVariables['uid']);

        $photo = new Photo();
        $photo->uid = $uriVariables['uid'];
        try {
            $photo->data = $this->photoProvider->getPhotoUtilisateur($utilisateur);
        } catch (PhotoIndisponibleException) {
            return null;
        }
        return $photo;
    }
}