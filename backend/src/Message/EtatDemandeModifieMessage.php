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

namespace App\Message;

readonly class EtatDemandeModifieMessage
{

    public function __construct(
        private int     $idDemande,
        private int     $idEtatprecedent,
        private int     $idEtat,
        private string  $uidUtilisateurModif,
        private ?string $commentaire,
        private ?int    $idProfil)
    {
    }

    public function getIdEtat(): int
    {
        return $this->idEtat;
    }

    public function getIdEtatprecedent(): int
    {
        return $this->idEtatprecedent;
    }

    public function getIdDemande(): int
    {
        return $this->idDemande;
    }

    public function getUidUtilisateurModif(): string
    {
        return $this->uidUtilisateurModif;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function getIdProfil(): ?int
    {
        return $this->idProfil;
    }

}