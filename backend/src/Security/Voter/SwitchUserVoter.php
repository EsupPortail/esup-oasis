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

namespace App\Security\Voter;

use App\Entity\Utilisateur;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SwitchUserVoter extends Voter
{
    public function __construct(
        private readonly Security $security,
        #[Autowire(env: 'APP_ENV')]
        private readonly string $environment,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'CAN_SWITCH_USER';
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        if ($this->environment === 'prod') {
            return false;
        }

        return $this->security->isGranted(Utilisateur::ROLE_ADMIN);
    }
}
