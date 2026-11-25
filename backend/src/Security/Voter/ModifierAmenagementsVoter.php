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

use App\ApiResource\Amenagement;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ModifierAmenagementsVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Amenagement::MODIFIER_AMENAGEMENTS_UTILISATEUR;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        if (in_array(Utilisateur::ROLE_GESTIONNAIRE, $token->getRoleNames())) {
            return true;
        }

        if (in_array(Utilisateur::ROLE_RENFORT, $token->getRoleNames())) {
            if ($subject instanceof Amenagement) {
                return ($subject->typeAmenagement ?? null)?->aideHumaine === true;
            }
            if (is_array($subject)) {
                [$previous, $new] = $subject;
                return (
                    ($previous?->typeAmenagement ?? null)?->aideHumaine === true
                    && ($new?->typeAmenagement ?? null)?->aideHumaine === true
                );
            }
        }

        return false;
    }
}
