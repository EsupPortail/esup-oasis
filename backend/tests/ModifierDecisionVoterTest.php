<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\ApiResource\DecisionAmenagementExamens;
use App\Entity\DecisionAmenagementExamens as Decision;
use App\Entity\Utilisateur;
use App\Security\Voter\ModifierDecisionVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ModifierDecisionVoterTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string, int}>
     */
    public static function transitionsProvider(): array
    {
        return [
            'saisie sur une décision en attente' => [Utilisateur::ROLE_GESTIONNAIRE, Decision::ETAT_ATTENTE_VALIDATION_CAS, Decision::ETAT_ATTENTE_VALIDATION_CAS, VoterInterface::ACCESS_GRANTED],
            'saisie sur une décision validée' => [Utilisateur::ROLE_GESTIONNAIRE, Decision::ETAT_VALIDE, Decision::ETAT_VALIDE, VoterInterface::ACCESS_GRANTED],
            'saisie sur une décision envoyée' => [Utilisateur::ROLE_ADMIN, Decision::ETAT_EDITION_DEMANDEE, Decision::ETAT_EDITION_DEMANDEE, VoterInterface::ACCESS_DENIED],
            'saisie sur une décision éditée' => [Utilisateur::ROLE_ADMIN, Decision::ETAT_EDITE, Decision::ETAT_EDITE, VoterInterface::ACCESS_DENIED],
            'demande d\'édition du gestionnaire' => [Utilisateur::ROLE_GESTIONNAIRE, Decision::ETAT_ATTENTE_VALIDATION_CAS, Decision::ETAT_VALIDE, VoterInterface::ACCESS_GRANTED],
            'envoi par le gestionnaire' => [Utilisateur::ROLE_GESTIONNAIRE, Decision::ETAT_VALIDE, Decision::ETAT_EDITION_DEMANDEE, VoterInterface::ACCESS_DENIED],
            'envoi par l\'administrateur' => [Utilisateur::ROLE_ADMIN, Decision::ETAT_VALIDE, Decision::ETAT_EDITION_DEMANDEE, VoterInterface::ACCESS_GRANTED],
            'décision en signature, par l\'administrateur' => [Utilisateur::ROLE_ADMIN, Decision::ETAT_EN_SIGNATURE, Decision::ETAT_EDITION_DEMANDEE, VoterInterface::ACCESS_DENIED],
            'reprise d\'une décision refusée' => [Utilisateur::ROLE_GESTIONNAIRE, Decision::ETAT_REFUSEE, Decision::ETAT_ATTENTE_VALIDATION_CAS, VoterInterface::ACCESS_GRANTED],
            'nouvelle demande sans reprise' => [Utilisateur::ROLE_GESTIONNAIRE, Decision::ETAT_REFUSEE, Decision::ETAT_VALIDE, VoterInterface::ACCESS_DENIED],
        ];
    }

    #[DataProvider('transitionsProvider')]
    public function testTransition(string $role, string $etatAvant, string $etatApres, int $attendu): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')->willReturn([$role]);

        $this->assertSame(
            $attendu,
            new ModifierDecisionVoter()->vote(
                $token,
                [$this->decision($etatAvant), $this->decision($etatApres)],
                [DecisionAmenagementExamens::MODIFIER_DECISION],
            ),
        );
    }

    public function testSupportsOnlyModifierDecision(): void
    {
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            new ModifierDecisionVoter()->vote($this->createMock(TokenInterface::class), [], ['OTHER_ATTRIBUTE']),
        );
    }

    private function decision(string $etat): DecisionAmenagementExamens
    {
        $decision = new DecisionAmenagementExamens();
        $decision->etat = $etat;

        return $decision;
    }
}
