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

namespace App\State\Demande;

use App\ApiResource\TableauDeBord;
use App\Entity\CharteDemandeur;
use App\Entity\Demande;
use App\Entity\EtatDemande;
use App\Entity\ModificationEtatDemande;
use App\Entity\OptionReponse;
use App\Entity\ProfilBeneficiaire;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Entity\TypologieHandicap;
use App\Entity\Utilisateur;
use App\Message\EtatDemandeModifieMessage;
use App\Repository\DemandeRepository;
use App\Repository\EtatDemandeRepository;
use App\Repository\ModificationEtatDemandeRepository;
use App\Repository\ProfilBeneficiaireRepository;
use App\Repository\ReponseRepository;
use App\Service\ErreurLdapException;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\Attribute\Required;

class DemandeManager
{

    use ClockAwareTrait;

    private UtilisateurManager $utilisateurManager;

    public function __construct(private readonly DemandeRepository                 $demandeRepository,
                                private readonly EtatDemandeRepository             $etatDemandeRepository,
                                private readonly ModificationEtatDemandeRepository $modificationEtatDemandeRepository,
                                private readonly ProfilBeneficiaireRepository      $profilBeneficiaireRepository,
                                private readonly ReponseRepository                 $reponseRepository,
                                private readonly MessageBusInterface               $messageBus,
                                private readonly Security                          $security)
    {

    }

    #[Required]
    public function setUtilisateurManager(UtilisateurManager $utilisateurManager): void
    {
        $this->utilisateurManager = $utilisateurManager;
    }


    public function getDemande(int $idDemande): ?Demande
    {
        return $this->demandeRepository->find($idDemande);
    }

    public function getEtatDemande(int $idEtat): ?EtatDemande
    {
        return $this->etatDemandeRepository->find($idEtat);
    }

    public function getProfil(int $idProfil): ?ProfilBeneficiaire
    {
        return $this->profilBeneficiaireRepository->find($idProfil);
    }

    public function logModificationEtat(int     $idDemande, int $idEtat, int $idEtatPrecedent,
                                        ?int    $idProfil, Utilisateur $utilisateur,
                                        ?string $commentaire): ModificationEtatDemande
    {
        $modif = new ModificationEtatDemande();
        $modif->setCommentaire($commentaire);
        $modif->setDemande($this->getDemande($idDemande));
        $modif->setEtat($this->getEtatDemande($idEtat));
        $modif->setEtatPrecedent($this->getEtatDemande($idEtatPrecedent));
        $modif->setProfil(match ($idProfil) {
            null => null,
            default => $this->getProfil($idProfil)
        }
        );
        $modif->setUtilisateur($utilisateur);
        $modif->setDateModification($this->now());

        $this->modificationEtatDemandeRepository->save($modif, true);
        return $modif;
    }

    /**
     * @param Demande $demande
     * @return bool
     */
    public function demandeAvecAccompagnement(Demande $demande): bool
    {
        return match ($demande->getCampagne()->getTypeDemande()->isAccompagnementOptionnel()) {
            false => true,
            default => current($this->reponseRepository->findBy([
                    'repondant' => $demande->getDemandeur(),
                    'campagne' => $demande->getCampagne(),
                    'question' => Question::QUESTION_DEMANDE_ACCOMPAGNEMENT,
                ]))->getOptionsChoisies()->current()->getId() === OptionReponse::OPTION_DEMANDE_ACCOMPAGNEMENT_OUI
        };
    }

    /**
     * @param Demande $demande
     * @param int $idEtat
     * @param string|null $commentaire
     * @param int|null $profilId
     * @param Utilisateur|string|null $user
     * @return Demande
     * @throws ErreurLdapException
     */
    public function modifierDemande(Demande $demande, int $idEtat, ?string $commentaire,
                                    ?int    $profilId = null, Utilisateur|string|null $user = null): Demande
    {
        $etat = $this->etatDemandeRepository->find($idEtat);
        $etatPrecedent = $demande->getEtat();
        $demande->setEtat($etat);

        if (null !== $profilId) {
            $profil = $this->profilBeneficiaireRepository->find($profilId);
            $demande->setProfilAttribue($profil);
        }

        //si demande passe à réceptionnée, on ajuste la date de dépot
        if ($etat !== $etatPrecedent && $idEtat === EtatDemande::RECEPTIONNEE) {
            $demande->setDateDepot($this->now());
        }

        $this->demandeRepository->save($demande, true);

        if (is_string($user)) {
            $user = $this->utilisateurManager->parUid($user);
        }
        $user = $user ?? $this->security->getUser();

        //les changements d'état peuvent déclencher des actions, on envoie un message "état modifié"
        if ($etatPrecedent !== $demande->getEtat()) {
            $this->messageBus->dispatch(
                new EtatDemandeModifieMessage(
                    $demande->getId(),
                    $etatPrecedent->getId(),
                    $demande->getEtat()->getId(),
                    $user->getUserIdentifier(),
                    $commentaire,
                    $profilId ?? $demande->getProfilAttribue()?->getId()
                )
            );
        }
        return $demande;
    }

    /**
     * @param Demande $demande
     * @return TypologieHandicap[]
     */
    public function getTypologiesHandicap(Demande $demande): array
    {
        $reponsesTypologies = array_filter(
            $this->reponseRepository->findBy([
                'repondant' => $demande->getDemandeur(),
                'campagne' => $demande->getCampagne(),
            ]),
            fn(Reponse $reponse) => $reponse->getQuestion()->getTableOptions() === 'typologie_handicap'
        );
        return empty($reponsesTypologies) ? [] : (current($reponsesTypologies))->getTypologiesHandicap()->toArray();
    }

    /**
     * Crée les chartes à valider correspondant à la demande
     *
     * @param Demande $demande
     * @return void
     */
    public function ajouterChartes(Demande $demande): void
    {
        $chartesDemandeurExistantes = array_map(
            fn(CharteDemandeur $charteDemandeur) => $charteDemandeur->getCharte()->getId(),
            $demande->getChartes()->toArray()
        );

        foreach ($demande->getProfilAttribue()->getChartes() as $charte) {
            if (in_array($charte->getId(), $chartesDemandeurExistantes)) {
                continue;
            }
            $charteDemandeur = new CharteDemandeur();
            $charteDemandeur->setCharte($charte);
            $charteDemandeur->setLibelle($charte->getLibelle());
            $charteDemandeur->setContenu($charte->getContenu());
            $demande->addCharte($charteDemandeur);
        }
        $this->demandeRepository->save($demande, true);
    }

    public function ajouterCommentaire(Demande $demande, ?string $commentaire): void
    {
        //zéro contrôle, texte brut, on prend ce qu'on a en entrée et basta
        $demande->setCommentaire($commentaire);
        $this->demandeRepository->save($demande, true);
    }

    /**
     *  Liste les demandes "en cours" ou "non conforme" liées à une campagne terminée
     *
     * @return Demande[]
     *
     */
    public function demandesObsoletes(): array
    {
        return $this->demandeRepository->demandesObsoletes();
    }

    /**
     * @param mixed $utilisateur
     * @param TableauDeBord $tdb
     * @return TableauDeBord
     * @throws ErreurLdapException
     */
    public function tableauDeBord(?\App\ApiResource\Utilisateur $utilisateur, TableauDeBord $tdb = new TableauDeBord()): TableauDeBord
    {
        if (null === $utilisateur) {
            $utilisateur = $this->security->getUser();
        } else {
            $utilisateur = $this->utilisateurManager->parUid($utilisateur->uid);
        }

        $demandesEnCours = $this->demandeRepository->findDemandesEnCours($utilisateur);
        $tdb->nbDemandesEnCours = count($demandesEnCours);

        $tdb->nbDemandesParEtat = [];
        foreach ($demandesEnCours as $demande) {
            $etatUri = \App\ApiResource\EtatDemande::COLLECTION_URI . '/' . $demande->getEtat()->getId();
            $tdb->nbDemandesParEtat[$etatUri] = ($tdb->nbDemandesParEtat[$etatUri] ?? 0) + 1;
        }

        return $tdb;
    }

}