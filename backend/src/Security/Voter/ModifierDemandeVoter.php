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

use App\ApiResource\Demande;
use App\Entity\EtatDemande;
use App\Entity\MembreCommission;
use App\Entity\Utilisateur;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ModifierDemandeVoter extends Voter
{
    use VoirDemandeTrait;

    public function __construct() {}

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Demande::MAJ_DEMANDE;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @param Vote|null $vote* @return bool
     * @return bool
     */
    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        //si on n'a pas le droit de lire, on n'a pas le droit d'écrire...
        if (!$this->peutVoirDemande($token, $subject[0])) {
            return false;
        }

        //les gestionnaires et admins peuvent tout faire
        if (in_array(Utilisateur::ROLE_GESTIONNAIRE, $token->getRoleNames())) {
            return true;
        }

        [$previous, $new] = $subject;

        //les renforts peuvent tout faire sauf passer à "refusé" / validé
        if (
            in_array(Utilisateur::ROLE_RENFORT_DEMANDES, $token->getRoleNames())
            && !in_array($new->etat->id, [EtatDemande::REFUSEE, EtatDemande::VALIDEE])
        ) {
            return true;
        }

        //l'utilisateur lui-même peut passer la demande à "réceptionnée" mais rien d'autre
        if ($token->getUserIdentifier() === $subject[0]->demandeur->uid) {
            if (
                $previous->typeDemande->id !== $new->typeDemande->id
                || $previous->demandeur->uid !== $new->demandeur->uid
                || null !== $new->profilAttribue
                || $previous->commentaire !== $new->commentaire
            ) {
                return false;
            }

            if (
                !in_array($previous->etat->id, [EtatDemande::EN_COURS, EtatDemande::NON_CONFORME])
                || EtatDemande::RECEPTIONNEE !== $new->etat->id
            ) {
                return false;
            }

            //le demandeur a simplement cliqué "valider ma demande"
            return true;
        }

        //membres de commission
        if (
            null !== $subject[0]->campagne->commission
            && in_array(Utilisateur::ROLE_MEMBRE_COMMISSION, $token->getRoleNames())
        ) {
            /**
             * @var Utilisateur $user
             */
            $user = $token->getUser();
            /**
             *
             */
            $membreCommission = array_filter(
                $user->getMembreCommissions()->toArray(),
                fn(MembreCommission $membre) => (
                    $membre->getCommission()->getId() === $previous->campagne->commission->id
                ),
            );
            if (!empty($membreCommission)) {
                $membreCommission = current($membreCommission);
                if (
                    null !== $new->profilAttribue
                    && !in_array(Utilisateur::ROLE_ATTRIBUER_PROFIL, $membreCommission->getRoles())
                ) {
                    return false;
                }
                //Quelles sont les modifs?
                if ($previous->etat->id !== $new->etat->id) {
                    //attribuer le profil
                    if (
                        in_array($new->etat->id, [EtatDemande::PROFIL_VALIDE, EtatDemande::REFUSEE])
                        && in_array(Utilisateur::ROLE_ATTRIBUER_PROFIL, $membreCommission->getRoles())
                    ) {
                        return true;
                    }
                    //valider la conformité
                    if (
                        in_array($new->etat->id, [EtatDemande::CONFORME, EtatDemande::NON_CONFORME])
                        && in_array(Utilisateur::ROLE_VALIDER_CONFORMITE_DEMANDE, $membreCommission->getRoles())
                    ) {
                        return true;
                    }
                    //envoyer à la commission
                    if ($new->etat->id == EtatDemande::ATTENTE_COMMISSION) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
