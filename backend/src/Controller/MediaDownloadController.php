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

namespace App\Controller;

use App\Entity\Fichier;
use App\Repository\FichierRepository;
use App\Service\FileStorage\StorageProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MediaDownloadController extends AbstractController
{

    #[Route('/fichiers/{fileId}')]
    public function getFile(int                      $fileId, FichierRepository $fichierRepository,
                            StorageProviderInterface $storageProvider): Response
    {
        $fichier = $fichierRepository->find($fileId);
        if (null === $fichier) {
            throw new FileNotFoundException("/fichiers/" . $fileId);
        }

        $this->denyAccessUnlessGranted(Fichier::VOIR_FICHIER, $fichier);

        $file = $storageProvider->get($fichier->getMetadata());
        if ($file instanceof File) {
            $file = $file->getContent();
        }
        $response = new Response($file);
        $disposition = HeaderUtils::makeDisposition(
            disposition: HeaderUtils::DISPOSITION_INLINE,
            filename: $fichier->getNom(),
            filenameFallback: $fichier->getId()
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $fichier->getTypeMime());
        return $response;
    }

}