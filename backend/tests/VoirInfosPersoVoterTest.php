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

use App\ApiResource\Utilisateur;
use App\Security\Voter\VoirInfosPersoVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class VoirInfosPersoVoterTest extends TestCase
{
    private Security $security;
    private VoirInfosPersoVoter $voter;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->voter = new VoirInfosPersoVoter($this->security);
    }

    public function testSupportsOnlyVoirInfosPerso(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new Utilisateur();

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $subject, ['OTHER_ATTRIBUTE']),
        );
    }

    public function testPlanificateurCanSeeInfosPerso(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'etudiant1';

        $token = $this->createMock(TokenInterface::class);
        $this->security
            ->method('isGranted')
            ->with(\App\Entity\Utilisateur::ROLE_PLANIFICATEUR)
            ->willReturn(true);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $subject, [Utilisateur::VOIR_INFOS_PERSO]),
        );
    }

    public function testUserCanSeeOwnInfosPerso(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'etudiant1';

        $currentUser = $this->createMock(UserInterface::class);
        $currentUser->method('getUserIdentifier')->willReturn('etudiant1');

        $token = $this->createMock(TokenInterface::class);
        $this->security
            ->method('isGranted')
            ->with(\App\Entity\Utilisateur::ROLE_PLANIFICATEUR)
            ->willReturn(false);
        $this->security
            ->method('getUser')
            ->willReturn($currentUser);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $subject, [Utilisateur::VOIR_INFOS_PERSO]),
        );
    }

    public function testOtherUserCannotSeeInfosPerso(): void
    {
        $subject = new Utilisateur();
        $subject->uid = 'etudiant1';

        $currentUser = $this->createMock(UserInterface::class);
        $currentUser->method('getUserIdentifier')->willReturn('autre_etudiant');

        $token = $this->createMock(TokenInterface::class);
        $this->security
            ->method('isGranted')
            ->with(\App\Entity\Utilisateur::ROLE_PLANIFICATEUR)
            ->willReturn(false);
        $this->security
            ->method('getUser')
            ->willReturn($currentUser);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $subject, [Utilisateur::VOIR_INFOS_PERSO]),
        );
    }
}
