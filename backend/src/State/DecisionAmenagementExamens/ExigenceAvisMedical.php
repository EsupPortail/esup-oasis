<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\State\DecisionAmenagementExamens;

use App\Entity\Beneficiaire;
use App\Entity\DecisionAmenagementExamens;

/**
 * Dit si l'édition d'une décision d'aménagements exige la date de l'avis médical : oui dès qu'un
 * profil de handicap du bénéficiaire, sur la période de la décision, active l'option.
 */
readonly class ExigenceAvisMedical
{
    public function estRequisePour(DecisionAmenagementExamens $decision): bool
    {
        $beneficiaire = $decision->getBeneficiaire();
        if (null === $beneficiaire || null === $decision->getDebut() || null === $decision->getFin()) {
            return false;
        }

        // accompagnés ou non : des aménagements d'examens se décident aussi sans accompagnement
        $profils = $beneficiaire->getBeneficiairesParIntervalle(
            $decision->getDebut(),
            $decision->getFin(),
            avecAccompagnement: false,
        );

        return array_any(
            $profils,
            // l'option ne vaut que pour un profil de handicap, celui qui porte une typologie
            fn(Beneficiaire $profil) => true === $profil->getProfil()?->isAvecTypologie()
                && true === $profil->getProfil()->isAvisMedicalRequis(),
        );
    }
}
