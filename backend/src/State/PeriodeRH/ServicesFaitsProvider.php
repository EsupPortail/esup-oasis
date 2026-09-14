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

namespace App\State\PeriodeRH;

use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\LigneServiceFait;
use App\ApiResource\PeriodeRH;
use App\ApiResource\ServicesFaits;
use App\ApiResource\TypeEvenement;
use App\ApiResource\Utilisateur as UtilisateurResource;
use App\Entity\Evenement;
use App\Entity\InterventionForfait;
use App\Entity\Parametre;
use App\Entity\TauxHoraire;
use App\Entity\TypeEvenement as TypeEvenementEntity;
use App\Entity\Utilisateur;
use App\Repository\EvenementRepository;
use App\Repository\InterventionForfaitRepository;
use App\Repository\PeriodeRHRepository;
use App\Service\ParametreService;
use Exception;

use function array_key_exists;
use function array_walk;
use function bcadd;
use function usort;

class ServicesFaitsProvider implements ProviderInterface
{
    private array $cachedResources;

    public function __construct(
        private readonly PeriodeRHRepository $periodeRHRepository,
        private readonly EvenementRepository $evenementRepository,
        private readonly InterventionForfaitRepository $interventionForfaitRepository,
        private readonly ParametreService $parametreService,
    ) {
        $this->cachedResources = [
            TypeEvenementEntity::class => [],
            Utilisateur::class => [],
            TauxHoraire::class => [],
        ];
    }

    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return object|array|null
     * @throws Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        //récup de la période
        $periode = $this->periodeRHRepository->find($uriVariables['id']);
        if (null === $periode) {
            throw new ItemNotFoundException("Aucune période pour l'id " . $uriVariables['id']);
        }

        //on construit l'objet
        $servicesFaits = $this->init(new PeriodeRH($periode), $uriVariables['uid'] ?? null);

        $evenements = $this->getEvenements($periode, $uriVariables['uid'] ?? null);
        return $this->traiterEvenements($evenements, $servicesFaits);
    }

    /**
     * @param \App\Entity\PeriodeRH $periode
     * @param                                 $uid
     * @return ServicesFaits
     * @throws Exception
     */
    public function init(PeriodeRH $periode, $uid): ServicesFaits
    {
        $servicesFaits = new ServicesFaits();
        $servicesFaits->id = $periode->id;
        $servicesFaits->periode = $periode;
        $servicesFaits->structure =
            $this->parametreService->valeur(Parametre::APP_SERVICE_DENOMINATION) ?? 'service Phase';
        $servicesFaits->lignes = [];
        $servicesFaits->uid = $uid ?? null;
        return $servicesFaits;
    }

    /**
     * @param \App\Entity\PeriodeRH $periode
     * @param string|null $uid
     * @return array<Evenement|InterventionForfait>
     */
    public function getEvenements(\App\Entity\PeriodeRH $periode, ?string $uid): array
    {
        $evenements = $this->evenementRepository->parPeriodeEtIntervenant($periode, $uid);
        $interventions = $this->interventionForfaitRepository->parPeriodeEtIntervenant($periode, $uid);

        return [...$evenements, ...$interventions];
    }

    /**
     * @param (Evenement|InterventionForfait)[] $evenements
     * @param ServicesFaits $servicesFaits
     * @return ServicesFaits
     * @throws ConfigurationIncompleteException
     */
    public function traiterEvenements(array $evenements, ServicesFaits $servicesFaits): ServicesFaits
    {
        //définition de la précision à utiliser dans les calculs - on prend large
        bcscale(8);
        foreach ($evenements as $evenement) {
            $intervenant = $evenement->getIntervenant();
            $type = $evenement->getType();
            $dateEvenement = $evenement instanceof Evenement ? $evenement->getDebut() : $servicesFaits->periode->debut;
            $tauxHoraire = $evenement->getType()->getTauxHoraireActifPourDate($dateEvenement);
            if (null === $tauxHoraire) {
                throw new ConfigurationIncompleteException(
                    'Taux horaire non renseigné pour ' . $evenement->getType()->getLibelle(),
                );
            }
            $ligneId = $intervenant->getId() . '###' . $type->getId() . '###' . $tauxHoraire->getId();
            $servicesFaits->lignes[$ligneId] = $this->append(
                $servicesFaits->lignes[$ligneId] ?? null,
                $evenement,
                $tauxHoraire,
            );
        }

        //on repasse les indices en auto
        $servicesFaits->lignes = array_values($servicesFaits->lignes);
        //on recoupe à deux chiffres après la virgule - voir https://stackoverflow.com/questions/1642614/how-to-ceil-floor-and-round-bcmath-numbers ?
        array_walk(array: $servicesFaits->lignes, callback: function (LigneServiceFait $ligne) {
            $ligne->nbHeures = bcadd($ligne->nbHeures, 0, 2);
        });

        //on trie par intervenant et type d'événement
        usort($servicesFaits->lignes, function (LigneServiceFait $a, LigneServiceFait $b) {
            if ($a->intervenant->nom === $b->intervenant->nom) {
                return $a->type->libelle <=> $b->type->libelle;
            }
            return $a->intervenant->nom <=> $b->intervenant->nom;
        });

        return $servicesFaits;
    }

    /**
     * @param LigneServiceFait|null $ligne
     * @param Evenement|InterventionForfait $evenement
     * @param TauxHoraire $tauxHoraire
     * @return LigneServiceFait
     * @throws Exception
     */
    protected function append(
        ?LigneServiceFait $ligne,
        Evenement|InterventionForfait $evenement,
        TauxHoraire $tauxHoraire,
    ): LigneServiceFait {
        if (null == $ligne) {
            $ligne = new LigneServiceFait();
            $ligne->type = new TypeEvenement($evenement->getType());
            $ligne->intervenant = new UtilisateurResource($evenement->getIntervenant()->getUtilisateur());
            $ligne->tauxHoraire = new \App\ApiResource\TauxHoraire($tauxHoraire);
            $ligne->nbHeures = 0;
        }

        //on ajoute la durée de l'événement
        $duree = $evenement instanceof Evenement ? $evenement->getDureeEnHeures() : $evenement->getHeures();
        $ligne->nbHeures = bcadd($ligne->nbHeures, $duree);

        return $ligne;
    }
}
