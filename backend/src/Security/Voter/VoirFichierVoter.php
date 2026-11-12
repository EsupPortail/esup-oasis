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
use App\ApiResource\Telechargement;
use App\Entity\Fichier;
use App\Entity\Utilisateur;
use App\Repository\FichierRepository;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VoirFichierVoter extends Voter
{
    use VoirDemandeTrait;

    public function __construct(
        private readonly Security $security,
        private readonly FichierRepository $fichierRepository,
    ) {}

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return (
            $attribute === Fichier::VOIR_FICHIER
            && ($subject instanceof Fichier || $subject instanceof Telechargement)
        );
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @param Vote|null $vote* @return bool
     */
    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        if ($subject instanceof Telechargement) {
            //on veut récupérer le fichier associé
            $subject = $this->fichierRepository->find($subject->id);
        }

        if (null === $subject) {
            return false;
        }

        if ($this->security->isGranted(Utilisateur::ROLE_GESTIONNAIRE)) {
            return true;
        }

        if ($this->security->isGranted(Utilisateur::ROLE_MEMBRE_COMMISSION) && $subject->getReponses()->count() > 0) {
            // On va voir si c'est lié à une réponse pour une demande
            // qui est gérée par une des commissions de l'utilisateur
            $demandeEntity = $subject->getReponses()->current()->getDemande();
            if (null !== $demandeEntity && $this->peutVoirDemande($token, new Demande($demandeEntity))) {
                return true;
            }
        }

        if ($this->security->isGranted(Utilisateur::ROLE_RENFORT)) {
            //On les autorise à voir les réponses aux questions pour les types de demandes non restreints
            $reponses = $subject->getReponses();
            $demandeEntity = $reponses->count() === 1 ? $reponses->current()->getDemande() : null;
            if (
                null !== $demandeEntity
                && !$demandeEntity->getCampagne()->getTypeDemande()->isVisibiliteLimitee()
            ) {
                //visible!
                return true;
            }
        }

        $userIdentifier = $token->getUser()?->getUserIdentifier();
        if (null === $userIdentifier) {
            return false;
        }

        if ($subject->getPieceJointeBeneficiaire()?->getBeneficiaire()?->getUserIdentifier() === $userIdentifier) {
            return true;
        }

        return $userIdentifier === $subject->getProprietaire()?->getUserIdentifier();
    }
}
