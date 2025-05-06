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
use App\Entity\TauxHoraire;
use App\Entity\TypeEvenement as TypeEvenementEntity;
use App\Entity\Utilisateur;
use App\Repository\PeriodeRHRepository;
use App\State\TransformerService;
use Exception;

class ServicesFaitsProvider implements ProviderInterface
{

    private array $cachedResources;

    public function __construct(private readonly PeriodeRHRepository $periodeRHRepository,
                                private readonly TransformerService  $transformerService)
    {
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
        $servicesFaits = $this->init($periode, $uriVariables['uid'] ?? null);

        $evenements = $this->getEvenements($periode, $uriVariables['uid'] ?? null);
        return $this->traiterEvenements($evenements, $servicesFaits);
    }

    /**
     * @param \App\Entity\PeriodeRH $periode
     * @param                                 $uid
     * @return ServicesFaits
     * @throws Exception
     */
    public function init(\App\Entity\PeriodeRH $periode, $uid): ServicesFaits
    {
        $periodeResource = $this->transformerService->transform($periode, PeriodeRH::class);

        $servicesFaits = new ServicesFaits();
        $servicesFaits->id = $periodeResource->id;
        $servicesFaits->periode = $periodeResource;
        $servicesFaits->structure = "Service Phase Pôle FIPVU"; //todo: param de conf quelque part...table parametre?
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
        return array_filter(
            array: [...$periode->getEvenements()->toArray(), ...$periode->getInterventionsForfait()],
            callback: function (Evenement|InterventionForfait $evenement) use ($uid) {
                if (null !== $uid) {
                    return $evenement->getIntervenant()->getUtilisateur()->getUid() === $uid;
                }
                return (!$evenement->getIntervenant()->getUtilisateur()->isGestionnaire());
            }
        );
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
                throw new ConfigurationIncompleteException('Taux horaire non renseigné pour ' . $evenement->getType()->getLibelle());
            }
            $ligneId = $intervenant->getId() . '###' . $type->getId() . '###' . $tauxHoraire->getId();
            $servicesFaits->lignes[$ligneId] = $this->append($servicesFaits->lignes[$ligneId] ?? null, $evenement, $tauxHoraire);
        }

        //on repasse les indices en auto
        $servicesFaits->lignes = array_values($servicesFaits->lignes);
        //on recoupe à deux chiffres après la virgule - voir https://stackoverflow.com/questions/1642614/how-to-ceil-floor-and-round-bcmath-numbers ?
        array_walk(array: $servicesFaits->lignes,
            callback: function (LigneServiceFait $ligne) {
                $ligne->nbHeures = bcadd($ligne->nbHeures, 0, 2);
            }
        );

        //on trie par intervenant et type d'événement
        usort($servicesFaits->lignes,
            function (LigneServiceFait $a, LigneServiceFait $b) {
                if ($a->intervenant->nom === $b->intervenant->nom) {
                    return $a->type->libelle <=> $b->type->libelle;
                }
                return $a->intervenant->nom <=> $b->intervenant->nom;
            }
        );

        return $servicesFaits;
    }

    /**
     * @param LigneServiceFait|null $ligne
     * @param Evenement|InterventionForfait $evenement
     * @param TauxHoraire $tauxHoraire
     * @return LigneServiceFait
     * @throws Exception
     */
    protected function append(?LigneServiceFait $ligne, Evenement|InterventionForfait $evenement, TauxHoraire $tauxHoraire): LigneServiceFait
    {
        if (null == $ligne) {
            $ligne = new LigneServiceFait();
            $ligne->type = $this->getTypeResource($evenement->getType());
            $ligne->intervenant = $this->getUtilisateurResource($evenement->getIntervenant()->getUtilisateur());
            $ligne->tauxHoraire = $this->getTauxHoraireResource($tauxHoraire);
            $ligne->nbHeures = 0;
        }

        //on ajoute la durée de l'événement
        $duree = $evenement instanceof Evenement ? $evenement->getDureeEnHeures() : $evenement->getHeures();
        $ligne->nbHeures = bcadd($ligne->nbHeures, $duree);

        return $ligne;
    }

    /**
     * @param TypeEvenementEntity $typeEntity
     * @return TypeEvenement
     * @throws Exception
     */
    public function getTypeResource(TypeEvenementEntity $typeEntity): TypeEvenement
    {
        if (!array_key_exists($typeEntity->getId(), $this->cachedResources[TypeEvenementEntity::class])) {
            $this->cachedResources[TypeEvenementEntity::class][$typeEntity->getId()] =
                $this->transformerService->transform($typeEntity, TypeEvenement::class);
        }
        return $this->cachedResources[TypeEvenementEntity::class][$typeEntity->getId()];
    }

    /**
     * @param Utilisateur $utilisateur
     * @return UtilisateurResource
     * @throws Exception
     */
    private function getUtilisateurResource(Utilisateur $utilisateur): UtilisateurResource
    {
        if (!array_key_exists($utilisateur->getId(), $this->cachedResources[Utilisateur::class])) {
            $this->cachedResources[Utilisateur::class][$utilisateur->getId()] =
                $this->transformerService->transform($utilisateur, UtilisateurResource::class);
        }
        return $this->cachedResources[Utilisateur::class][$utilisateur->getId()];
    }

    /**
     * @param TauxHoraire $tauxHoraire
     * @return \App\ApiResource\TauxHoraire
     * @throws Exception
     */
    private function getTauxHoraireResource(TauxHoraire $tauxHoraire): \App\ApiResource\TauxHoraire
    {
        if (!array_key_exists($tauxHoraire->getId(), $this->cachedResources[TauxHoraire::class])) {
            $this->cachedResources[TauxHoraire::class][$tauxHoraire->getId()] =
                $this->transformerService->transform($tauxHoraire, \App\ApiResource\TauxHoraire::class);
        }
        return $this->cachedResources[TauxHoraire::class][$tauxHoraire->getId()];
    }


}