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
    private readonly array $cleanSuffixes;

    public function __construct(
        #[Autowire('%env(UNICAMPUS_SGBD)%')]
        private readonly string $sgbd,
        #[Autowire('%env(UNICAMPUS_USER)%')]
        private readonly string $user,
        #[SensitiveParameter]
        #[Autowire('%env(UNICAMPUS_PWD)%')]
        private readonly string $password,
        #[Autowire('%env(UNICAMPUS_SID)%')]
        private readonly string $sid,
        #[Autowire('%env(json:UNICAMPUS_SUFFIXES)%')]
        array $suffixes,
        private readonly LoggerInterface $logger,
    ) {
        $this->cleanSuffixes = array_map(
            fn($suffix) => preg_replace('/[^a-zA-Z0-9]/', '', (string) $suffix),
            $suffixes,
        );
    }

    #[Override]
    public function getPhotoUtilisateur(Utilisateur $utilisateur): string
    {
        try {
            $this->connect();
        } catch (Exception $e) {
            throw new PhotoIndisponibleException($e->getMessage());
        }

        $numEtudiant = preg_replace('/[^a-zA-Z0-9]/', '', (string) $utilisateur->getNumeroEtudiant());
        if ($numEtudiant === '') {
            throw new PhotoIndisponibleException('Photo disponible uniquement pour les étudiants');
        }

        $identifiants = [];
        foreach ($this->cleanSuffixes as $suffix) {
            $identifiants[] = $numEtudiant . $suffix;
        }

        $listeIdentifiants = implode("','", $identifiants);

        $sql = "select id_personne, stockage_photo from unicampus.personnes
                where id_personne in ('$listeIdentifiants')
                and stockage_photo is not null
                and diffphoto = 1";

        $parseAndReturnFirstResult = match (strtolower($this->sgbd)) {
            'oracle' => $this->parseAndReturnFirstResultOracle(...),
            default => $this->parseAndReturnFirstResultPostgre(...),
        };

        try {
            return $parseAndReturnFirstResult($sql);
        } catch (PhotoIndisponibleException $e) {
            $this->logger->error($e->getMessage() . '(n°' . $utilisateur->getNumeroEtudiant() . ')');
            throw $e;
        }
    }

    /**
     * @throws PhotoIndisponibleException
     */
    private function parseAndReturnFirstResultOracle(string $sql): string
    {
        $stmt = oci_parse($this->db, $sql);

        if (!oci_execute($stmt)) {
            throw new PhotoIndisponibleException('Erreur lors de la récupération de l\'étudiant');
        }

        $row = oci_fetch_array($stmt, OCI_BOTH + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
        if (!$row) {
            $this->logger->info('Pas de photo dans la base unicampus pour cet étudiant');
            throw new PhotoIndisponibleException('Pas de photo pour cet étudiant');
        }

        return $row['STOCKAGE_PHOTO'];
    }

    /**
     * @throws PhotoIndisponibleException
     */
    private function parseAndReturnFirstResultPostgre(string $sql): string
    {
        $result = pg_query($this->db, $sql);
        if (!$result) {
            throw new PhotoIndisponibleException('Erreur lors de la récupération de l\'étudiant');
        }

        $row = pg_fetch_array($result, null, PGSQL_BOTH);
        if (!$row) {
            throw new PhotoIndisponibleException('Pas de photo pour cet étudiant');
        }

        return pg_unescape_bytea($row['stockage_photo']);
    }

    /**
     * @return false|resource
     * @throws Exception
     */
    private function connect(): mixed
    {
        if (!is_resource($this->db)) {
            if (strtolower($this->sgbd) == 'oracle') {
                $this->doConnectOracle();
            } else {
                $this->doConnectPostgre();
            }
        }

        return $this->db;
    }

    /**
     * @throws Exception
     */
    private function doConnectOracle(): void
    {
        $this->db = @oci_connect($this->user, $this->password, $this->sid, 'UTF-8');
        if (false === $this->db) {
            $message = 'Connexion à la base Unicampus impossible';
            $this->logger->error($message);
            throw new Exception($message);
        }
    }

    /**
     * @throws Exception
     */
    private function doConnectPostgre(): void
    {
        $this->db = @pg_connect(
            "host={$this->sid} port=5432 dbname=unicampus user={$this->user} password={$this->password}",
        );
        if (false === $this->db) {
            $message = 'Connexion à la base Unicampus impossible';
            $this->logger->error($message);
            throw new Exception($message);
        }
    }
}
