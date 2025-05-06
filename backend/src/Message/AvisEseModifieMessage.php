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

use App\Entity\AvisEse;
use App\Entity\Utilisateur;
use App\Util\AnneeUniversitaireAwareTrait;

class AvisEseModifieMessage
{
    use AnneeUniversitaireAwareTrait;

    protected Utilisateur $beneficiaire;

    public function __construct(AvisEse $avis)
    {
        $this->beneficiaire = $avis->getUtilisateur();
    }

    /**
     * @return array les bornes de l'année universitaire pour laquelle on veut mettre à jour la décision
     */
    public function getBornesAnnee(): array
    {
        /**
         * On veut la date de début d'année universitaire pour laquelle le dernierBenef est valide
         */

        return $this->bornesAnneeDuJour($this->getDebutAnneeUniversitairePourBeneficiaires($this->beneficiaire->getBeneficiaires()->toArray()));
    }

    public function getBeneficiaire(): Utilisateur
    {
        return $this->beneficiaire;
    }
}