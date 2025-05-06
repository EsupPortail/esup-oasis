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

use App\ApiResource\Demande;
use App\ApiResource\Telechargement;
use App\Entity\Fichier;
use App\Entity\Utilisateur;
use App\Repository\FichierRepository;
use App\State\TransformerService;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VoirFichierVoter extends Voter
{
    use VoirDemandeTrait;

    public function __construct(private readonly Security           $security,
                                private readonly FichierRepository  $fichierRepository,
                                private readonly TransformerService $transformerService)
    {
    }

    #[Override] protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Fichier::VOIR_FICHIER && ($subject instanceof Fichier || $subject instanceof Telechargement);
    }

    /**
     * @param string $attribute
     * @param Fichier $subject
     * @param TokenInterface $token
     * @return bool
     */
    #[Override] protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($subject instanceof Telechargement) {
            //on veut récupérer le fichier associé
            $subject = $this->fichierRepository->find($subject->id);
        }

        if ($this->security->isGranted(Utilisateur::ROLE_GESTIONNAIRE)) {
            return true;
        }

        if ($this->security->isGranted(Utilisateur::ROLE_MEMBRE_COMMISSION) &&
            $subject->getReponses()->count() > 0) {
            // On va voir si c'est lié à une réponse pour une demande
            // qui est gérée par une des commissions de l'utilisateur
            $demande = $this->transformerService->transform(($subject->getReponses()->current())->getDemande(), Demande::class);

            if ($this->peutVoirDemande($token, $demande)) {
                return true;
            }
        }

        if ($this->security->isGranted(Utilisateur::ROLE_RENFORT)) {
            //On les autorise à voir les réponses aux questions pour les types de demandes non restreints
            $reponses = $subject->getReponses();
            if ($reponses->count() === 1 && !$reponses->current()->getDemande()->getCampagne()->getTypeDemande()->isVisibiliteLimitee()) {
                //visible!
                return true;
            }
        }


        return $token->getUser()->getUserIdentifier() == $subject->getProprietaire()->getUserIdentifier();
    }
}