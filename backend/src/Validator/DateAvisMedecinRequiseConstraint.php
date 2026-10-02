<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Refuse l'édition de la décision sans date d'avis médical, pour les seuls bénéficiaires dont un
 * profil l'exige (ProfilBeneficiaire::avisMedicalRequis) : sans elle, le visa du document resterait à trous.
 */
#[Attribute]
class DateAvisMedecinRequiseConstraint extends Constraint
{
    public string $message = "Veuillez saisir une date d'avis médical afin de générer le document.";

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
