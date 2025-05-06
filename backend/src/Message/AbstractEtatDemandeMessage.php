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

use App\Entity\Demande;
use App\Entity\TypeDemande;
use App\Entity\Utilisateur;
use App\State\Demande\DemandeManager;

abstract class AbstractEtatDemandeMessage
{
    private ?Demande $demande = null;

    public function __construct(protected readonly EtatDemandeModifieMessage $message,
                                private readonly DemandeManager              $demandeManager)
    {

    }

    public function getDemande(): Demande
    {
        if (null === $this->demande) {
            $this->demande = $this->demandeManager->getDemande($this->message->getIdDemande());
        }
        return $this->demande;
    }

    public function getDemandeur(): Utilisateur
    {
        return $this->getDemande()->getDemandeur();
    }

    public function getTypeDemande(): TypeDemande
    {
        return $this->getDemande()->getCampagne()->getTypeDemande();
    }

    public function getCommentaire(): ?string
    {
        return $this->message->getCommentaire();
    }

    public function getIdProfil(): ?int
    {
        return $this->message->getIdProfil();
    }

    public function getUidUtilisateur(): string
    {
        return $this->message->getUidUtilisateurModif();
    }

}