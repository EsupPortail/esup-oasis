<?php

namespace App\Security\Voter;

use App\ApiResource\Reponse;
use App\Entity\EtatDemande;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ModifierReponseVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Reponse::MODIFIER_REPONSE && $subject instanceof Reponse;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        assert($subject instanceof Reponse);

        if (in_array(Utilisateur::ROLE_GESTIONNAIRE, $token->getRoleNames())) {
            return true;
        }

        if ($subject->demande->demandeur->uid === $token->getUserIdentifier()) {
            return in_array(
                $subject->demande->etat?->id,
                [EtatDemande::EN_COURS, EtatDemande::NON_CONFORME],
                true,
            );
        }

        return false;
    }
}
