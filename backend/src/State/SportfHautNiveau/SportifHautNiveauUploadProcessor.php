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

namespace App\State\SportfHautNiveau;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ListeSportifsHautNiveau;
use App\ApiResource\SportifHautNiveau;
use App\Repository\FichierRepository;
use App\Repository\SportifHautNiveauRepository;
use App\Service\FileStorage\StorageProviderInterface;
use App\State\TransformerService;
use Exception;
use Symfony\Component\HttpFoundation\File\File;

readonly class SportifHautNiveauUploadProcessor implements ProcessorInterface
{
    public function __construct(private StorageProviderInterface    $storageProvider,
                                private FichierRepository           $fichierRepository,
                                private SportifHautNiveauRepository $sportifHautNiveauRepository,
                                private TransformerService          $transformerService)
    {
    }

    /**
     * @param SportifHautNiveau $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return ListeSportifsHautNiveau
     * @throws Exception
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ListeSportifsHautNiveau
    {
        $fichier = $this->fichierRepository->find($data->telechargement->id);
        $contenu = $this->storageProvider->get($fichier->getMetadata());

        if ($contenu instanceof File) {
            $contenu = $contenu->getContent();
        }

        //on vide la table et on recharge
        $this->sportifHautNiveauRepository->truncate();

        //on crée un stream à partir de la chaine
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contenu);
        rewind($handle);

        while ($line = fgetcsv($handle, separator: ';')) {
            [$identifiant, $annee] = $line;
            if (trim($identifiant) == '') {
                continue;
            }
            $sportif = new \App\Entity\SportifHautNiveau();
            $sportif->setIdentifiantExterne($identifiant);
            $sportif->setAnneeNaissance($annee);
            $this->sportifHautNiveauRepository->save($sportif);// pas de flush à chaque ligne...
        }
        if (isset($sportif)) {
            //forcer le flush
            $this->sportifHautNiveauRepository->save($sportif, true);
        }

        $liste = new ListeSportifsHautNiveau();
        $liste->sportifs = array_map(
            fn($sportif) => $this->transformerService->transform($sportif, SportifHautNiveau::class),
            $this->sportifHautNiveauRepository->findAll()
        );
        return $liste;
    }
}