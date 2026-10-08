<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests\Validator;

use App\ApiResource\DecisionAmenagementExamens as DecisionResource;
use App\Entity\DecisionAmenagementExamens;
use App\Validator\DateAvisMedecinRequiseConstraint;
use App\Validator\DateAvisMedecinRequiseConstraintValidator;
use DateTime;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DateAvisMedecinRequiseConstraintValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DateAvisMedecinRequiseConstraintValidator
    {
        return new DateAvisMedecinRequiseConstraintValidator();
    }

    public function testEditionWithoutDateIsAllowedWhenNotRequired(): void
    {
        // aucun profil n'exige l'avis : comportement inchangé
        $this->validator->validate(
            $this->decision(etat: DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE, requise: false),
            new DateAvisMedecinRequiseConstraint(),
        );

        $this->assertNoViolation();
    }

    public function testEditionWithoutDateIsRefusedWhenRequired(): void
    {
        $contrainte = new DateAvisMedecinRequiseConstraint();

        $this->validator->validate(
            $this->decision(etat: DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE, requise: true),
            $contrainte,
        );

        $this->buildViolation($contrainte->message)->atPath('property.path.dateAvisMedecin')->assertRaised();
    }

    public function testEditionWithDateIsAllowedWhenRequired(): void
    {
        $this->validator->validate(
            $this->decision(
                etat: DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE,
                requise: true,
                date: new DateTime('2026-09-01'),
            ),
            new DateAvisMedecinRequiseConstraint(),
        );

        $this->assertNoViolation();
    }

    public function testGestionnaireRequestIsAlsoRefusedWithoutDate(): void
    {
        $contrainte = new DateAvisMedecinRequiseConstraint();

        $this->validator->validate(
            $this->decision(etat: DecisionAmenagementExamens::ETAT_VALIDE, requise: true),
            $contrainte,
        );

        $this->buildViolation($contrainte->message)->atPath('property.path.dateAvisMedecin')->assertRaised();
    }

    public function testOtherStatesAreNotConcerned(): void
    {
        // une décision en attente, ou déjà éditée, ne produit pas de document
        foreach ([DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS, DecisionAmenagementExamens::ETAT_EDITE] as $etat) {
            $this->validator->validate(
                $this->decision(etat: $etat, requise: true),
                new DateAvisMedecinRequiseConstraint(),
            );
        }

        $this->assertNoViolation();
    }

    private function decision(string $etat, bool $requise, ?DateTime $date = null): DecisionResource
    {
        $decision = new DecisionResource();
        $decision->dateAvisMedecinRequise = $requise;
        $decision->etat = $etat;
        $decision->dateAvisMedecin = $date;

        return $decision;
    }
}
