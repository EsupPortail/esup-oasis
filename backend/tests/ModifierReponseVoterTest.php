<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Tests;

use App\ApiResource\Demande;
use App\ApiResource\Reponse;
use App\ApiResource\Utilisateur;
use App\Security\Voter\ModifierReponseVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ModifierReponseVoterTest extends TestCase
{
    private ModifierReponseVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new ModifierReponseVoter();
    }

    public function testDemandeurCanModifyTheirOwnResponse(): void
    {
        $demandeur = new Utilisateur();
        $demandeur->uid = 'demandeur123';

        $etat = new \App\ApiResource\EtatDemande();
        $etat->id = \App\Entity\EtatDemande::EN_COURS;

        $demande = new Demande();
        $demande->demandeur = $demandeur;
        $demande->etat = $etat;

        $reponse = new Reponse();
        $reponse->demande = $demande;

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('demandeur123');

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $token,
            $reponse,
            [Reponse::MODIFIER_REPONSE],
        ));
    }

    public function testDemandeurCannotModifyResponseWhenDemandeReceptionnee(): void
    {
        $demandeur = new Utilisateur();
        $demandeur->uid = 'demandeur123';

        $etat = new \App\ApiResource\EtatDemande();
        $etat->id = \App\Entity\EtatDemande::RECEPTIONNEE;

        $demande = new Demande();
        $demande->demandeur = $demandeur;
        $demande->etat = $etat;

        $reponse = new Reponse();
        $reponse->demande = $demande;

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('demandeur123');

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $token,
            $reponse,
            [Reponse::MODIFIER_REPONSE],
        ));
    }

    public function testGestionnaireCanModifyAnyResponse(): void
    {
        $demandeur = new Utilisateur();
        $demandeur->uid = 'demandeur123';

        $demande = new Demande();
        $demande->demandeur = $demandeur;

        $reponse = new Reponse();
        $reponse->demande = $demande;

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('gestionnaire123');

        // This is what the current voter checks: $token->hasAttribute(Utilisateur::ROLE_GESTIONNAIRE)
        $token->method('getRoleNames')->willReturn(['ROLE_GESTIONNAIRE']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $token,
            $reponse,
            [Reponse::MODIFIER_REPONSE],
        ));
    }

    public function testOtherUserCannotModifyResponse(): void
    {
        $demandeur = new Utilisateur();
        $demandeur->uid = 'demandeur123';

        $demande = new Demande();
        $demande->demandeur = $demandeur;

        $reponse = new Reponse();
        $reponse->demande = $demande;

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('other123');
        $token->method('hasAttribute')->willReturn(false);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $token,
            $reponse,
            [Reponse::MODIFIER_REPONSE],
        ));
    }
}
