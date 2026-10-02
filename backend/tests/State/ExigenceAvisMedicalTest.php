<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests\State;

use App\Entity\Beneficiaire;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\ProfilBeneficiaire;
use App\Entity\Utilisateur;
use App\State\DecisionAmenagementExamens\ExigenceAvisMedical;
use DateTime;
use PHPUnit\Framework\TestCase;

final class ExigenceAvisMedicalTest extends TestCase
{
    public function testNoProfilRequiresMedicalOpinionByDefault(): void
    {
        // instance qui n'a rien paramétré : comportement historique
        $decision = $this->decisionPour($this->profil(avisMedicalRequis: false));

        self::assertFalse((new ExigenceAvisMedical())->estRequisePour($decision));
    }

    public function testProfilWithOptionRequiresMedicalOpinion(): void
    {
        $decision = $this->decisionPour($this->profil(avisMedicalRequis: true));

        self::assertTrue((new ExigenceAvisMedical())->estRequisePour($decision));
    }

    public function testOneProfilWithOptionIsEnough(): void
    {
        // sportif de haut niveau et en situation de handicap : le second profil l'emporte
        $decision = $this->decisionPour(
            $this->profil(avisMedicalRequis: false),
            $this->profil(avisMedicalRequis: true),
        );

        self::assertTrue((new ExigenceAvisMedical())->estRequisePour($decision));
    }

    public function testProfilOutsideDecisionPeriodDoesNotCount(): void
    {
        // profil de handicap clos avant l'année de la décision
        $decision = $this->decisionPour(
            $this->profil(avisMedicalRequis: true, debut: '2023-09-01', fin: '2024-08-31'),
        );

        self::assertFalse((new ExigenceAvisMedical())->estRequisePour($decision));
    }

    public function testProfilWithoutTypologieNeverRequiresMedicalOpinion(): void
    {
        // case restée cochée sur un profil qui n'est plus un profil de handicap
        $decision = $this->decisionPour($this->profil(avisMedicalRequis: true, avecTypologie: false));

        self::assertFalse((new ExigenceAvisMedical())->estRequisePour($decision));
    }

    public function testProfilCountsWithoutAccompagnement(): void
    {
        // des aménagements d'examens sans accompagnement restent soumis à l'avis médical
        $decision = $this->decisionPour(
            $this->profil(avisMedicalRequis: true, avecAccompagnement: false),
        );

        self::assertTrue((new ExigenceAvisMedical())->estRequisePour($decision));
    }

    private function profil(
        bool $avisMedicalRequis,
        string $debut = '2025-09-01',
        ?string $fin = '2026-08-31',
        bool $avecAccompagnement = true,
        bool $avecTypologie = true,
    ): Beneficiaire {
        $profil = (new ProfilBeneficiaire())
            ->setAvisMedicalRequis($avisMedicalRequis)
            ->setAvecTypologie($avecTypologie);

        return (new Beneficiaire())
            ->setProfil($profil)
            ->setDebut(new DateTime($debut))
            ->setFin(null === $fin ? null : new DateTime($fin))
            ->setAvecAccompagnement($avecAccompagnement);
    }

    private function decisionPour(Beneficiaire ...$profils): DecisionAmenagementExamens
    {
        $utilisateur = new Utilisateur();
        foreach ($profils as $profil) {
            $utilisateur->addBeneficiaire($profil);
        }

        return (new DecisionAmenagementExamens())
            ->setBeneficiaire($utilisateur)
            ->setDebut(new DateTime('2025-09-01'))
            ->setFin(new DateTime('2026-08-31'));
    }
}
