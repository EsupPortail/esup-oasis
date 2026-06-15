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

use App\ApiResource\Photo;
use App\Entity\Commission;
use App\Entity\Demande;
use App\Entity\MembreCommission;
use App\Entity\Utilisateur;
use App\State\Utilisateur\UtilisateurManager;
use App\Security\Voter\VoirPhotoVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

readonly class TestUtilisateurManager extends UtilisateurManager
{
    public function __construct(private Utilisateur $userToReturn)
    {
        // No parent constructor call to avoid mocking dependencies of readonly class
    }

    public function parUid(string $uid, bool $creerSiNouveau = false): Utilisateur
    {
        return $this->userToReturn;
    }
}

class VoirPhotoVoterTest extends TestCase
{
    private Security $security;
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsOnlyVoirPhotoAttribute(): void
    {
        $dummyUser = $this->createMock(Utilisateur::class);
        $utilisateurManager = new TestUtilisateurManager($dummyUser);
        $voter = new VoirPhotoVoter($this->security, $utilisateurManager);

        $subject = new Photo();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token, $subject, [Photo::VOIR_PHOTO])
        );

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->token, $subject, ['OTHER_ATTRIBUTE'])
        );
    }

    public function testGestionnaireCanSeePhoto(): void
    {
        $dummyUser = $this->createMock(Utilisateur::class);
        $utilisateurManager = new TestUtilisateurManager($dummyUser);
        $voter = new VoirPhotoVoter($this->security, $utilisateurManager);

        $subject = new Photo();
        $subject->uid = 'test_uid';

        $this->security
            ->method('isGranted')
            ->willReturnCallback(fn($attribute) => $attribute === Utilisateur::ROLE_GESTIONNAIRE);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token, $subject, [Photo::VOIR_PHOTO])
        );
    }

    public function testMembreCommissionCanSeePhotoIfDemandeurInTheirCommission(): void
    {
        $subject = new Photo();
        $subject->uid = 'demandeur123';

        // Current user setup
        $currentUser = $this->createMock(Utilisateur::class);
        
        // Mock commission and campaign
        $campaign = new \App\Entity\CampagneDemande();
        
        $commission = $this->createMock(Commission::class);
        $commission->method('getCampagnes')->willReturn(new ArrayCollection([$campaign]));

        $membreCommission = $this->createMock(MembreCommission::class);
        $membreCommission->method('getCommission')->willReturn($commission);

        $currentUser->method('getMembreCommissions')->willReturn(new ArrayCollection([$membreCommission]));

        // Demandeur setup
        $demandeur = $this->createMock(Utilisateur::class);
        $demande = $this->createMock(Demande::class);
        $demande->method('getCampagne')->willReturn($campaign);
        $demandeur->method('getDemandes')->willReturn(new ArrayCollection([$demande]));

        // Create UtilisateurManager mock replacement
        $utilisateurManager = new TestUtilisateurManager($demandeur);
        $voter = new VoirPhotoVoter($this->security, $utilisateurManager);

        // Security mocks
        $this->security->method('getUser')->willReturn($currentUser);
        $this->security
            ->method('isGranted')
            ->willReturnCallback(fn($attribute) => match ($attribute) {
                Utilisateur::ROLE_GESTIONNAIRE => false,
                Utilisateur::ROLE_MEMBRE_COMMISSION => true,
                default => false,
            });

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token, $subject, [Photo::VOIR_PHOTO])
        );
    }

    public function testMembreCommissionCannotSeePhotoIfDemandeurNotInTheirCommission(): void
    {
        $subject = new Photo();
        $subject->uid = 'demandeur123';

        // Current user setup
        $currentUser = $this->createMock(Utilisateur::class);
        
        // Mock commission and campaign
        $campaign = new \App\Entity\CampagneDemande();
        $otherCampaign = new \App\Entity\CampagneDemande();
        
        $commission = $this->createMock(Commission::class);
        $commission->method('getCampagnes')->willReturn(new ArrayCollection([$campaign]));

        $membreCommission = $this->createMock(MembreCommission::class);
        $membreCommission->method('getCommission')->willReturn($commission);

        $currentUser->method('getMembreCommissions')->willReturn(new ArrayCollection([$membreCommission]));

        // Demandeur setup
        $demandeur = $this->createMock(Utilisateur::class);
        $demande = $this->createMock(Demande::class);
        $demande->method('getCampagne')->willReturn($otherCampaign); // different campaign
        $demandeur->method('getDemandes')->willReturn(new ArrayCollection([$demande]));

        // Create UtilisateurManager mock replacement
        $utilisateurManager = new TestUtilisateurManager($demandeur);
        $voter = new VoirPhotoVoter($this->security, $utilisateurManager);

        // Security mocks
        $this->security->method('getUser')->willReturn($currentUser);
        $this->security
            ->method('isGranted')
            ->willReturnCallback(fn($attribute) => match ($attribute) {
                Utilisateur::ROLE_GESTIONNAIRE => false,
                Utilisateur::ROLE_MEMBRE_COMMISSION => true,
                default => false,
            });

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token, $subject, [Photo::VOIR_PHOTO])
        );
    }

    public function testOtherUsersAccessDenied(): void
    {
        $dummyUser = $this->createMock(Utilisateur::class);
        $utilisateurManager = new TestUtilisateurManager($dummyUser);
        $voter = new VoirPhotoVoter($this->security, $utilisateurManager);

        $subject = new Photo();
        $subject->uid = 'demandeur123';

        $this->security
            ->method('isGranted')
            ->willReturn(false);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token, $subject, [Photo::VOIR_PHOTO])
        );
    }
}
