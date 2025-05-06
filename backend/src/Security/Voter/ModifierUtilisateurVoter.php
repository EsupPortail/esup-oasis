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

use App\ApiResource\Utilisateur;
use App\Entity\Utilisateur as UtilisateurEntity;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ModifierUtilisateurVoter extends Voter
{
    private array $rolesAIgnorer;

    public function __construct(private readonly Security $security)
    {
        $this->rolesAIgnorer = [
            UtilisateurEntity::ROLE_DEMANDEUR,
            UtilisateurEntity::ROLE_USER,
        ];

    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Utilisateur::CAN_PATCH_USER && is_array($subject);
    }

    /**
     * @param string $attribute
     * @param Utilisateur[] $subject
     * @param TokenInterface $token
     * @return bool
     * @throws Exception
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /**
         * Utilisé uniquement sur un PATCH, on passe un tableau [previous_object, object]
         */
        [$previous, $new] = $subject;

        /**
         * numéro anonyme - même les admins ne peuvent pas le modifier!
         */
        if ($previous->numeroAnonyme !== $new->numeroAnonyme) {
            if (null !== $previous->numeroAnonyme) {
                return false;
            }
            if (!$this->security->isGranted(UtilisateurEntity::ROLE_GESTIONNAIRE)) {
                return false;
            }
        }

        if ($this->security->isGranted(UtilisateurEntity::ROLE_ADMIN)) {
            return true;
        }

        /**
         * Services
         */
        $addedServices = array_filter($new->services, fn($service) => !in_array($service, $previous->services));
        $removedServices = array_filter($previous->services, fn($service) => !in_array($service, $new->services));

        if (!empty([...$addedServices, ...$removedServices])) {
            return $this->security->isGranted(UtilisateurEntity::ROLE_ADMIN); //à voir?
        }

        /**
         * Roles
         */
        $addedRoles = array_filter($new->roles, fn($role) => !in_array($role, $previous->roles) && $role !== UtilisateurEntity::ROLE_USER);
        $removedRoles = array_filter($previous->roles, fn($role) => !in_array($role, $new->roles) && $role !== UtilisateurEntity::ROLE_USER);

        foreach ([...$addedRoles, ...$removedRoles] as $role) {
            if (!$this->canChange($role)) {
                return false;
            }
        }


        $user = $this->security->getUser();
        assert($user instanceof UtilisateurEntity);
        return $this->security->isGranted(UtilisateurEntity::ROLE_PLANIFICATEUR)
            || $user->getUid() === $new->uid;
    }

    private function canChange(string $role): bool
    {
        if ($role === UtilisateurEntity::ROLE_ADMIN) {
            return false;
        }
        if (in_array($role, $this->rolesAIgnorer)) {
            return true; //whatever, role calculé, pas besoin qu'il soit en entrée
        }

        //les gestionnaires peuvent déclarer les renforts
        if ($this->security->isGranted(UtilisateurEntity::ROLE_GESTIONNAIRE)
            && $role == UtilisateurEntity::ROLE_RENFORT) {
            return true;
        }

        //les renforts peuvent déclarer des bénéficiaires / les intervenants
        if (($this->security->isGranted(UtilisateurEntity::ROLE_PLANIFICATEUR))
            && in_array($role, [
                UtilisateurEntity::ROLE_BENEFICIAIRE,
                UtilisateurEntity::ROLE_INTERVENANT,
            ])) {
            return true;
        }

        return false;
    }


}