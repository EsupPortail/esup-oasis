<?php

/*
 * Copyright (c) 2024. Esup - Université de Bordeaux.
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
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VoirAmenagementsUtilisateurVoter extends Voter
{
    public function __construct(private readonly UtilisateurManager $utilisateurManager)
    {

    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Amenagement::VOIR_AMENAGEMENTS_UTILISATEUR;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (in_array(Utilisateur::ROLE_PLANIFICATEUR, $token->getRoleNames())) {
            return true;
        }

        //Pour les référents composantes, on vérifie en fonction des inscriptions actives du bénéficiaire
        if (in_array(Utilisateur::ROLE_REFERENT_COMPOSANTE, $token->getRoleNames())) {
            //ok pour la fonctionnalité, mais sur ce bénéficiaire?
            $beneficiaire = $this->utilisateurManager->parUid($subject);
            /**
             * @var Utilisateur $utilisateur
             */
            $utilisateur = $token->getUser();

            foreach ($beneficiaire->getInscriptionsEnCours() as $inscription) {
                if ($utilisateur->getComposantes()->contains($inscription->getFormation()->getComposante())) {
                    return true;
                }
            }

        }
        return false;
    }
}