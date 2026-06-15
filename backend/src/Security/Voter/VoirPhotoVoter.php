<?php

namespace App\Security\Voter;

use App\ApiResource\Photo;
use App\Entity\Commission;
use App\Entity\Utilisateur;
use App\Service\ErreurLdapException;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VoirPhotoVoter extends Voter
{
    public function __construct(
        private readonly Security $security,
        private readonly UtilisateurManager $utilisateurManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Photo::VOIR_PHOTO;
    }

    /**
     * @param string $attribute
     * @param Photo $subject
     * @param TokenInterface $token
     * @param Vote|null $vote
     * @return bool
     * @throws ErreurLdapException
     */
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        if ($this->security->isGranted(Utilisateur::ROLE_GESTIONNAIRE)) {
            return true;
        }

        if ($this->security->isGranted(Utilisateur::ROLE_MEMBRE_COMMISSION)) {
            //est-ce que la photo est celle d'un demandeur pour une de ses commissions ?
            $user = $this->security->getUser();
            assert($user instanceof Utilisateur);
            $commissions = $user->getMembreCommissions()->map(fn($mc) => $mc->getCommission());
            $demandeur = $this->utilisateurManager->parUid($subject->uid);
            foreach ($demandeur->getDemandes() as $demande) {
                foreach ($commissions as $commission) {
                    if ($commission->getCampagnes()->contains($demande->getCampagne())) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
