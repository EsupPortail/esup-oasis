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

namespace App\Service\Photo;

use App\Entity\Utilisateur;
use Exception;
use Override;
use Psr\Log\LoggerInterface;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class UnicampusProvider implements PhotoProviderInterface
{

    private $db;

    public function __construct(#[Autowire('%env(UNICAMPUS_USER)%')] private readonly string         $user,
                                #[SensitiveParameter]
                                #[Autowire('%env(UNICAMPUS_PWD)%')] private readonly string          $password,
                                #[Autowire('%env(UNICAMPUS_SID)%')] private readonly string          $sid,
                                #[Autowire('%env(json:UNICAMPUS_SUFFIXES)%')] private readonly array $suffixes,
                                private readonly LoggerInterface                                     $logger)
    {

    }


    #[Override] public function getPhotoUtilisateur(Utilisateur $utilisateur): string
    {
        try {
            $this->connect();
        } catch (Exception $e) {
            throw new PhotoIndisponibleException($e->getMessage());
        }

        if (null == $utilisateur->getNumeroEtudiant()) {
            throw new PhotoIndisponibleException('Photo disponible uniquement pour les étudiants');
        }

        $identifiants = [];
        foreach ($this->suffixes as $suffix) {
            $identifiants[] = $utilisateur->getNumeroEtudiant() . $suffix;
        }

        $listeIdentifiants = implode("','", $identifiants);

        $sql = "select id_personne, stockage_photo from unicampus.personnes
                where id_personne in ('$listeIdentifiants')
                and stockage_photo is not null
                and diffphoto = 1";

        $stmt = oci_parse($this->db, $sql);

        if (!oci_execute($stmt)) {
            $this->logger->error("Erreur d'exécution de la requête de récupération de la photo de l'étudiant " . $utilisateur->getNumeroEtudiant());
            throw new PhotoIndisponibleException('Erreur lors de la récupération de l\'étudiant n°' . $utilisateur->getNumeroEtudiant());
        }

        //On prend la première ligne retournée...tant pis pour les étudiants qui auraient deux photos sur
        // deux suffixes différents avec le même n° et ne récupèreraient pas la plus jolie...
        $row = oci_fetch_array($stmt, OCI_BOTH + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
        if (!$row) {
            $this->logger->info('Pas de photo dans la base unicampus pour le n° ' . $utilisateur->getNumeroEtudiant());
            throw new PhotoIndisponibleException('Pas de photo pour l\'étudiant n°' . $utilisateur->getNumeroEtudiant());
        }

        return $row['STOCKAGE_PHOTO'];
    }

    /**
     * @return false|resource
     * @throws Exception
     */
    private function connect()
    {
        if (!is_resource($this->db)) {
            $this->db = @oci_connect($this->user, $this->password, $this->sid, 'UTF-8');
            if (false === $this->db) {
                $message = 'Connexion à la base Unicampus impossible';
                $this->logger->error($message);
                throw new Exception($message);
            }
        }

        return $this->db;
    }

}