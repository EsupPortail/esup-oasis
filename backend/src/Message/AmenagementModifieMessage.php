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

namespace App\Message;

use App\Entity\Amenagement;
use App\Entity\Utilisateur;
use App\Util\AnneeUniversitaireAwareTrait;
use Exception;

class AmenagementModifieMessage
{
    use AnneeUniversitaireAwareTrait;

    protected Utilisateur $beneficiaire;
    protected bool $isDecision;

    public function __construct(
        private readonly Amenagement $amenagement,
    ) {
        $this->beneficiaire = $amenagement->getBeneficiaires()->current()->getUtilisateur();
        $this->isDecision = $this->amenagement->getType()->isDecision();
    }

    /**
     * @return array les bornes de l'année universitaire pour laquelle on veut mettre à jour la décision
     * @throws Exception
     */
    public function getBornesAnnee(): array
    {
        /**
         * On veut la date de début d'année universitaire pour laquelle le dernierBenef est valide
         */

        return $this->bornesAnneeDuJour(
            $this->getDebutAnneeUniversitairePourBeneficiaires($this->amenagement->getBeneficiaires()->toArray()),
        );
    }

    public function getBeneficiaire(): Utilisateur
    {
        return $this->beneficiaire;
    }

    public function isDecision(): bool
    {
        return $this->isDecision;
    }
}
