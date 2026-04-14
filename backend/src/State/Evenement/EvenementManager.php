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

namespace App\State\Evenement;

use App\ApiResource\Evenement as EvenementResource;
use App\ApiResource\TableauDeBord;
use App\ApiResource\TypeEquipement;
use App\ApiResource\Utilisateur;
use App\Entity\AvisEse;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\Evenement;
use App\Entity\ProfilBeneficiaire;
use App\Entity\TypeEvenement;
use App\Repository\AmenagementRepository;
use App\Repository\BeneficiaireRepository;
use App\Repository\CampusRepository;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Repository\EvenementRepository;
use App\Repository\TypeEquipementRepository;
use App\Repository\TypeEvenementRepository;
use App\Repository\UtilisateurRepository;
use App\State\MajBeneficiairesTrait;
use App\State\Utilisateur\UtilisateurManager;
use App\Util\AnneeUniversitaireAwareTrait;
use DateMalformedStringException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Messenger\MessageBusInterface;

class EvenementManager
{
    use ClockAwareTrait;
    use MajBeneficiairesTrait;
    use AnneeUniversitaireAwareTrait;

    public function __construct(
        private readonly EvenementRepository $evenementRepository,
        private readonly TypeEvenementRepository $typeEvenementRepository,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly UtilisateurManager $utilisateurManager,
        private readonly BeneficiaireRepository $beneficiaireRepository,
        private readonly CampusRepository $campusRepository,
        private readonly TypeEquipementRepository $typeEquipementRepository,
        private readonly DecisionAmenagementExamensRepository $decisionAmenagementExamensRepository,
        private readonly AmenagementRepository $amenagementRepository,
        private readonly Security $security,
        private readonly MessageBusInterface $messageBus,
    ) {}

    /**
     * @param EvenementResource $data
     * @return Evenement
     */
    public function maj(EvenementResource $data): Evenement
    {
        if (null !== $data->id) {
            $entity = $this->evenementRepository->find($data->id);
            $entity->setUtilisateurModification($this->security->getUser());
            $entity->setDateModification($this->now());
        } else {
            $entity = new Evenement();
            $entity->setUtilisateurCreation($this->security->getUser());
            $entity->setDateCreation($this->now());
        }

        //quoi?
        $entity->setLibelle($data->libelle);
        $entity->setType($this->typeEvenementRepository->find($data->type->id));

        //quand?
        $entity->setDebut($data->debut);
        $entity->setFin($data->fin);
        $entity->setTempsPreparation($data->tempsPreparation);
        $entity->setTempsSupplementaire($data->tempsSupplementaire);

        //qui?
        $this->majBeneficiaires($data->beneficiaires ?? [], $entity);

        $entity->setIntervenant(match ($data->intervenant) {
            null => null,
            default => $this->utilisateurRepository->findOneBy([
                'uid' => $data->intervenant->uid,
            ])?->getIntervenant(),
        });
        $this->majSuppleants($data->suppleants, $entity);
        $this->majEnseignants($data->enseignants ?? [], $entity);

        //où?
        $entity->setSalle($data->salle);
        $entity->setCampus($this->campusRepository->find($data->campus->id));

        //comment?
        $this->majEquipements($data->equipements, $entity);

        //etat
        $entity->setDateAnnulation($data->dateAnnulation);
        if ($entity->getType()->getId() === TypeEvenement::TYPE_RENFORT) {
            if ($data->valide && null === $entity->getDateValidation()) {
                $entity->setDateValidation($this->now());
            }
            if (!$data->valide && null !== $entity->getDateValidation()) {
                $entity->setDateValidation(null); //autorisé?
            }
        }

        $this->evenementRepository->save($entity, true);

        return $entity;
    }

    /**
     * @param Utilisateur[] $suppleants
     * @param Evenement $entity
     * @return void
     */
    private function majSuppleants(array $suppleants, Evenement $entity): void
    {
        foreach ($suppleants as $ressource) {
            $intervenant = $this->utilisateurRepository->findOneBy([
                'uid' => $ressource->uid,
            ])->getIntervenant();
            if (!$entity->getSuppleants()->contains($intervenant)) {
                $entity->addSuppleant($intervenant);
            }
        }
        foreach ($entity->getSuppleants() as $intervenant) {
            foreach ($suppleants as $ressource) {
                if ($ressource->uid === $intervenant->getUtilisateur()->getUid()) {
                    continue 2;
                }
            }
            $entity->removeSuppleant($intervenant);
        }
    }

    /**
     * @param Utilisateur[] $enseignants
     * @param Evenement $entity
     * @return void
     */
    private function majEnseignants(array $enseignants, Evenement $entity): void
    {
        foreach ($enseignants as $ressource) {
            $enseignant = $this->utilisateurManager->parUid($ressource->uid);
            if (null === $enseignant->getId()) {
                $this->utilisateurRepository->save($enseignant);
            }
            if (!$entity->getEnseignants()->contains($enseignant)) {
                $entity->addEnseignant($enseignant);
            }
        }
        foreach ($entity->getEnseignants() as $enseignant) {
            foreach ($enseignants as $ressource) {
                if ($ressource->uid === $enseignant->getUid()) {
                    continue 2;
                }
            }
            $entity->removeEnseignant($enseignant);
        }
    }

    /**
     * @param TypeEquipement[] $equipements
     * @param Evenement|null $entity
     * @return void
     */
    private function majEquipements(array $equipements, ?Evenement $entity): void
    {
        foreach ($equipements as $ressource) {
            $type = $this->typeEquipementRepository->find($ressource->id);
            if (!$entity->getEquipements()->contains($type)) {
                $entity->addEquipement($type);
            }
        }
        foreach ($entity->getEquipements() as $type) {
            foreach ($equipements as $ressource) {
                if ($ressource->id === $type->getId()) {
                    continue 2;
                }
            }
            $entity->removeEquipement($type);
        }
    }

    public function delete(EvenementResource $data): void
    {
        $existant = $this->evenementRepository->find($data->id);
        if (null !== $existant) {
            $this->evenementRepository->remove($existant, true);
        }
    }

    /**
     * @param Utilisateur|null $utilisateur
     * @param TableauDeBord|null $tdb
     * @return TableauDeBord
     * @throws DateMalformedStringException
     */
    public function tableauDeBord(
        ?Utilisateur $utilisateur = null,
        TableauDeBord $tdb = new TableauDeBord(),
    ): TableauDeBord {
        $now = $this->now();
        $jourPrecedent = $now->modify('-1 day');
        $debutSemaine = new DatePoint('monday this week');
        $finSemaine = (clone $debutSemaine)->modify('+6 days');
        $debutSemainePrec = (clone $debutSemaine)->modify('-1 week');
        $finSemainePrec = (clone $finSemaine)->modify('-1 week');
        $debutMois = new DatePoint('first day of this month');
        $finMois = new DatePoint('last day of this month');
        $debutMoisPrec = new DatePoint('first day of last month');
        $finMoisPrec = new DatePoint('last day of last month');
        $ilyaDeuxMois = (clone $now)->modify('-2 months');

        //todo: uniquement des count()

        $tdb->evenementsJour = $this->evenementRepository->countEvenementsDuJour($now, $utilisateur);
        $tdb->evolutionJour =
            $tdb->evenementsJour - $this->evenementRepository->countEvenementsDuJour($jourPrecedent, $utilisateur);
        $tdb->evenementsSemaine = $this->evenementRepository->countByDateInterval(
            debut: $debutSemaine,
            fin: $finSemaine,
            utilisateur: $utilisateur,
        );
        $tdb->evolutionSemaine =
            $tdb->evenementsSemaine
            - $this->evenementRepository->countByDateInterval(
                debut: $debutSemainePrec,
                fin: $finSemainePrec,
                utilisateur: $utilisateur,
            );
        $tdb->evenementsMois = $this->evenementRepository->countByDateInterval(
            debut: $debutMois,
            fin: $finMois,
            utilisateur: $utilisateur,
        );
        $tdb->evolutionMois =
            $tdb->evenementsMois
            - $this->evenementRepository->countByDateInterval(
                debut: $debutMoisPrec,
                fin: $finMoisPrec,
                utilisateur: $utilisateur,
            );

        $jPlus7 = $now->modify('+7 day');
        $jPlus30 = $now->modify('+30 day');

        $tdb->evenementsNonAffectesJour = $this->evenementRepository->countEvenementsDuJour($now, $utilisateur, true);
        $tdb->evenementsNonAffectesSemaine = $this->evenementRepository->countByDateInterval(
            debut: $now,
            fin: $jPlus7,
            nonAffectes: true,
            utilisateur: $utilisateur,
        );
        $tdb->evenementsNonAffectesMois = $this->evenementRepository->countByDateInterval(
            debut: $now,
            fin: $jPlus30,
            nonAffectes: true,
            utilisateur: $utilisateur,
        );
        $tdb->totalEvenementsNonAffectes = $this->evenementRepository->countByDateInterval(
            debut: $now,
            nonAffectes: true,
            utilisateur: $utilisateur,
        );

        $tdb->evenementsEnAttenteDeValidation = count($this->evenementRepository->findValidables($utilisateur));

        $tdb->evenementsSansBeneficiaire = $this->evenementRepository->countByDateInterval(
            debut: $ilyaDeuxMois,
            utilisateur: $utilisateur,
            sansBeneficiaire: true,
        );

        /**
         * TODO: requêtes dédiées pour chaque item
         */

        //bénéficiaires sans profil
        $tdb->nbBeneficiaires = $this->beneficiaireRepository->countActifs($now);

        /**
         * Ajouts 2024 : nb bénéficiaires, nb intervenants en cours,
         * aménagements saisis sur benefs en cours, états avis / décisions
         */

        $tdb->nbBeneficiairesIncomplets = $this->beneficiaireRepository->countActifsParProfil(
            $now,
            ProfilBeneficiaire::A_DETERMINER,
        );
        $tdb->nbAvisEseEnAttente = $this->utilisateurRepository->count([
            'etatAvisEse' => AvisEse::ETAT_EN_ATTENTE,
        ]);

        //décisions
        $bornesAnnee = $this->bornesAnneeDuJour($now);
        $tdb->nbDecisionsAttenteValidation = $this->beneficiaireRepository->countActifsParEtatDecisionAnneeU(
            $now,
            $bornesAnnee,
            DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS,
        );

        $tdb->nbDecisionsAEditer = $this->beneficiaireRepository->countActifsParEtatDecisionAnneeU(
            $now,
            $bornesAnnee,
            DecisionAmenagementExamens::ETAT_VALIDE,
        );

        //aménagements
        $tdb->nbAmenagementsEnCours = $this->amenagementRepository->countEnCours($now);
        //        $tdb->nbAmenagementsEnCours = count($this->amenagementRepository->findEnCours($now));

        //intervenants
        $tdb->nbIntervenants = $this->utilisateurRepository->countIntervenantsActifs();

        return $tdb;
    }

    /**
     * Retourne la liste des événements existants pour l'utilisateur dans cet intervalle
     *
     * @param Utilisateur $utilisateur
     * @param             $debutIntervalle
     * @param             $finIntervalle
     * @return Evenement[]
     */
    public function occupationsUtilisateur(Utilisateur $utilisateur, $debutIntervalle, $finIntervalle): array
    {
        return $this->evenementRepository->occupationsUtilisateur($utilisateur, $debutIntervalle, $finIntervalle);
    }
}
