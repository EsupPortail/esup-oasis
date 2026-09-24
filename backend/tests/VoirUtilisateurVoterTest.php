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

use App\ApiResource\Formation;
use App\ApiResource\Inscription;
use App\ApiResource\Utilisateur;
use App\Entity\Beneficiaire;
use App\Entity\CampagneDemande;
use App\Entity\Commission;
use App\Entity\Composante;
use App\Entity\Demande;
use App\Entity\Evenement;
use App\Entity\Intervenant;
use App\Entity\MembreCommission;
use App\Repository\DemandeRepository;
use App\Security\Voter\VoirUtilisateurVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoirUtilisateurVoterTest extends TestCase
{
    private Security $security;
    private DemandeRepository $demandeRepository;
    private VoirUtilisateurVoter $voter;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->demandeRepository = $this->createMock(DemandeRepository::class);
        $this->voter = new VoirUtilisateurVoter($this->security, $this->demandeRepository);
    }

    public function testVoteOnSelfReturnsTrue(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'user123';

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('user123');

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $token,
            $subject,
            [Utilisateur::VOIR_UTILISATEUR],
        ));
    }

    public function testPlanificateurCanSeeAnyone(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'user123';

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('other_user');

        $this->security
            ->method('isGranted')
            ->willReturnCallback(fn($attribute) => $attribute === \App\Entity\Utilisateur::ROLE_PLANIFICATEUR);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $token,
            $subject,
            [Utilisateur::VOIR_UTILISATEUR],
        ));
    }

    public function testEveryoneCanSeeGestionnaires(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'gest123';
        $subject->roles = ['ROLE_USER', 'ROLE_GESTIONNAIRE'];

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('any_user');

        $this->security
            ->method('isGranted')
            ->willReturnCallback(fn($attribute, $subj = null) => match ($attribute) {
                \App\Entity\Utilisateur::ROLE_PLANIFICATEUR => false,
                \App\Entity\Utilisateur::ROLE_GESTIONNAIRE => $subj === $subject,
                default => false,
            });

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $token,
            $subject,
            [Utilisateur::VOIR_UTILISATEUR],
        ));
    }

    public function testReferentComposanteCanSeeStudentsInTheirComposante(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'student123';

        // Initialize resources with property hooks
        $composanteResource = new \App\ApiResource\Composante();
        $propId = new ReflectionProperty($composanteResource, 'id');
        $propId->setValue($composanteResource, 1);

        $formationResource = new Formation();
        $propComp = new ReflectionProperty($formationResource, 'composante');
        $propComp->setValue($formationResource, $composanteResource);

        $inscriptionResource = new Inscription();
        $propForm = new ReflectionProperty($inscriptionResource, 'formation');
        $propForm->setValue($inscriptionResource, $formationResource);

        $subject->inscriptions = [$inscriptionResource];

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('referent123');

        $userCourant = $this->createMock(\App\Entity\Utilisateur::class);
        $composanteEntity = $this->createMock(Composante::class);
        $composanteEntity->method('getId')->willReturn(1);
        $userCourant->method('getComposantes')->willReturn(new ArrayCollection([$composanteEntity]));

        $this->security->method('getUser')->willReturn($userCourant);
        $this->security
            ->method('isGranted')
            ->willReturnCallback(fn($attribute) => match ($attribute) {
                \App\Entity\Utilisateur::ROLE_PLANIFICATEUR => false,
                \App\Entity\Utilisateur::ROLE_GESTIONNAIRE => false,
                \App\Entity\Utilisateur::ROLE_REFERENT_COMPOSANTE => true,
                default => false,
            });

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $token,
            $subject,
            [Utilisateur::VOIR_UTILISATEUR],
        ));
    }

    public function testMembreCommissionCanSeeStudentsInTheirCommission(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'student123';

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('membre123');

        $userCourant = $this->createMock(\App\Entity\Utilisateur::class);
        $commission = $this->createMock(Commission::class);
        $commission->method('getId')->willReturn(10);

        $membreCommission = $this->createMock(MembreCommission::class);
        $membreCommission->method('getCommission')->willReturn($commission);

        $userCourant->method('getMembreCommissions')->willReturn(new ArrayCollection([$membreCommission]));

        $campagne = $this->createMock(CampagneDemande::class);
        $campagne->method('getCommission')->willReturn($commission);

        $demande = $this->createMock(Demande::class);
        $demande->method('getCampagne')->willReturn($campagne);

        $this->demandeRepository->method('findByUid')->with('student123')->willReturn([$demande]);

        $this->security->method('getUser')->willReturn($userCourant);
        $this->security
            ->method('isGranted')
            ->willReturnCallback(fn($attribute) => match ($attribute) {
                \App\Entity\Utilisateur::ROLE_PLANIFICATEUR => false,
                \App\Entity\Utilisateur::ROLE_GESTIONNAIRE => false,
                \App\Entity\Utilisateur::ROLE_REFERENT_COMPOSANTE => false,
                \App\Entity\Utilisateur::ROLE_MEMBRE_COMMISSION => true,
                default => false,
            });

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $token,
            $subject,
            [Utilisateur::VOIR_UTILISATEUR],
        ));
    }

    public function testIntervenantCanSeeBeneficiaryOfTheirEvents(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'student123';

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('intervenant123');

        $userCourant = $this->createMock(\App\Entity\Utilisateur::class);
        $intervenant = $this->createMock(Intervenant::class);

        $beneficiaire = $this->createMock(Beneficiaire::class);
        $beneficiaireUtilisateur = $this->createMock(\App\Entity\Utilisateur::class);
        $beneficiaireUtilisateur->method('getUid')->willReturn('student123');
        $beneficiaire->method('getUtilisateur')->willReturn($beneficiaireUtilisateur);

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('getBeneficiaires')->willReturn(new ArrayCollection([$beneficiaire]));

        $intervenant->method('getInterventions')->willReturn(new ArrayCollection([$evenement]));
        $intervenant->method('getInterventionsForfait')->willReturn(new ArrayCollection());

        $userCourant->method('getIntervenant')->willReturn($intervenant);

        $this->security->method('getUser')->willReturn($userCourant);
        $this->security
            ->method('isGranted')
            ->willReturnCallback(fn($attribute) => match ($attribute) {
                \App\Entity\Utilisateur::ROLE_PLANIFICATEUR => false,
                \App\Entity\Utilisateur::ROLE_GESTIONNAIRE => false,
                \App\Entity\Utilisateur::ROLE_INTERVENANT => true,
                default => false,
            });

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $token,
            $subject,
            [Utilisateur::VOIR_UTILISATEUR],
        ));
    }

    public function testIntervenantCanSeeCreatorOfTheirEvents(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'creator123';
        $subject->roles = ['ROLE_USER', 'ROLE_GESTIONNAIRE'];

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('intervenant123');

        $userCourant = $this->createMock(\App\Entity\Utilisateur::class);
        $intervenant = $this->createMock(Intervenant::class);

        $creatorUtilisateur = $this->createMock(\App\Entity\Utilisateur::class);
        $creatorUtilisateur->method('getUid')->willReturn('creator123');

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('getUtilisateurCreation')->willReturn($creatorUtilisateur);
        $evenement->method('getBeneficiaires')->willReturn(new ArrayCollection());

        $intervenant->method('getInterventions')->willReturn(new ArrayCollection([$evenement]));
        $intervenant->method('getInterventionsForfait')->willReturn(new ArrayCollection());

        $userCourant->method('getIntervenant')->willReturn($intervenant);

        $this->security->method('getUser')->willReturn($userCourant);
        $this->security
            ->method('isGranted')
            ->willReturnCallback(fn($attribute) => match ($attribute) {
                \App\Entity\Utilisateur::ROLE_PLANIFICATEUR => false,
                \App\Entity\Utilisateur::ROLE_GESTIONNAIRE => false,
                \App\Entity\Utilisateur::ROLE_INTERVENANT => true,
                default => false,
            });

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(
            $token,
            $subject,
            [Utilisateur::VOIR_UTILISATEUR],
        ));
    }

    public function testAccessDeniedForOthers(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'student123';
        $subject->inscriptions = [];

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('random_user');

        $userCourant = $this->createMock(\App\Entity\Utilisateur::class);
        $userCourant->method('getComposantes')->willReturn(new ArrayCollection());
        $userCourant->method('getMembreCommissions')->willReturn(new ArrayCollection());

        $this->security->method('getUser')->willReturn($userCourant);
        $this->security->method('isGranted')->willReturn(false);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(
            $token,
            $subject,
            [Utilisateur::VOIR_UTILISATEUR],
        ));
    }
}
