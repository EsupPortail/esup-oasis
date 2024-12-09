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

namespace App\State\Evenement;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ActiviteBilanFinancier;
use App\ApiResource\BilanFinancier;
use App\ApiResource\IntervenantBilanFinancier;
use App\ApiResource\PeriodeRH;
use App\ApiResource\TauxHoraire;
use App\ApiResource\TypeEvenement;
use App\ApiResource\Utilisateur;
use App\Entity\Evenement;
use App\Entity\InterventionForfait;
use App\Entity\Parametre;
use App\Entity\PeriodeRH as PeriodeRHEntity;
use App\Entity\TauxHoraire as TauxHoraireEntity;
use App\Entity\TypeEvenement as TypeEvenementEntity;
use App\Entity\Utilisateur as UtilisateurEntity;
use App\Repository\ParametreRepository;
use App\State\TransformerService;
use Exception;

class BilanFinancierProvider implements ProviderInterface
{
    protected array $cachedResources;

    public function __construct(private readonly CollectionProvider  $provider,
                                private readonly TransformerService  $transformerService,
                                private readonly ParametreRepository $parametreRepository)
    {
        $this->cachedResources = [
            TypeEvenementEntity::class => [],
            UtilisateurEntity::class => [],
            TauxHoraireEntity::class => [],
            PeriodeRHEntity::class => [],
        ];
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /**
         * On veut tous les événements entre le 01/01 et le 31/12 de l'année demandée +
         * toutes les interventions au forfait dont les dates de début et de fin sont comprises également
         */

        $evenementsOperation = $operation->withClass(Evenement::class)
            ->withPaginationEnabled(false);

        $interventionsOperation = $operation->withClass(InterventionForfait::class)
            ->withPaginationEnabled(false);

        $context['filters']['exists']['intervenant'] = true;
        $context['filters']['intervalle']['debut'] = $uriVariables['debut'];
        $context['filters']['intervalle']['fin'] = $uriVariables['fin'];
        $cleanUriVariables = array_filter($uriVariables, fn($key) => !in_array($key, ['debut', 'fin']), ARRAY_FILTER_USE_KEY);
        $contextEvenements = $context;
        $contextEvenements['filters']['exists']['dateAnnulation'] = false; //on ne tient pas compte des événements annulés !
        //maybe: ajouter filtre envoyé RH en systématique?

        $contextInterventions = $context;
        $coeffChargeParam = $this->parametreRepository->findOneBy([
            'cle' => Parametre::COEFFICIENT_CHARGES,
        ]);

        /**
         * @var Evenement[] $evenements
         */
        $evenements = $this->provider->provide($evenementsOperation, $cleanUriVariables, $contextEvenements);

        /**
         * @var InterventionForfait[] $interventions
         */
        $interventions = $this->provider->provide($interventionsOperation, $cleanUriVariables, $contextInterventions);

        $intervenants = [];

        $bilan = new BilanFinancier();
        $bilan->debut = $uriVariables['debut'];
        $bilan->fin = $uriVariables['fin'];

        foreach ([...$evenements, ...$interventions] as $item) {
            /**
             * @var Evenement|InterventionForfait $item
             */
            if (!array_key_exists($item->getIntervenant()->getUtilisateur()->getId(), $intervenants)) {
                $intervenants[$item->getIntervenant()->getUtilisateur()->getId()] = new IntervenantBilanFinancier(
                    intervenant: $this->getUtilisateurResource($item->getIntervenant()->getUtilisateur())
                );
            }
            $dateItem = $item instanceof Evenement ? $item->getDebut() : $item->getPeriode()->getFin();
            $periode = $this->getPeriodeResource($item instanceof Evenement ? $item->getPeriodePriseEnCompteRH() : $item->getPeriode());
            $duree = $item instanceof Evenement ? $item->getDureeEnHeures() : $item->getHeures();
            $tauxEntity = $item->getType()->getTauxHoraireActifPourDate(
                match (true) {
                    $item instanceof Evenement => $item->getDebut(),
                    $item instanceof InterventionForfait => $item->getPeriode()->getFin(),
                    default => null
                }
            );
            if (null !== $tauxEntity) {
                $taux = $this->getTauxHoraireResource($tauxEntity);
            }
            $intervenants[$item->getIntervenant()->getUtilisateur()->getId()]->ajoutActivite(
                periode: $periode,
                type: $this->getTypeResource($item->getType()),
                tauxHoraire: $taux ?? null,
                nbHeures: $duree,
                coeffCharge: $coeffChargeParam->getValeurPourDate($dateItem)?->getValeur() ?? '1'
            );
        }

        $bilan->intervenants = array_values($intervenants);
        /**
         * Tri par intervenant-nom, intervenant-prénom, période, type événement
         *
         */
        usort(array: $bilan->intervenants,
            callback: fn(IntervenantBilanFinancier $a, IntervenantBilanFinancier $b) => match (true) {
                $a->intervenant->nom === $b->intervenant->nom => $a->intervenant->prenom <=> $b->intervenant->prenom,
                default => $a->intervenant->nom <=> $b->intervenant->nom
            }
        );
        $compareActivites = function (ActiviteBilanFinancier $a, ActiviteBilanFinancier $b) {
            return match (true) {
                $a->periode->id == $b->periode->id => $a->typeEvenement->libelle <=> $b->typeEvenement->libelle,
                default => $a->periode->debut <=> $b->periode->debut
            };
        };
        foreach ($bilan->intervenants as $intervenant) {
            usort($intervenant->activitesParPeriode, $compareActivites);
        }
        $bilan->periodes = array_values($this->cachedResources[PeriodeRHEntity::class]);//todo: build this explicitly if caching changes

        usort($bilan->periodes, fn($a, $b) => $a->debut <=> $b->debut);

        return $bilan;
    }


    /**
     * todo: move those into a CachedTransformerService or something
     */

    public function getPeriodeResource(PeriodeRHEntity $periodeRH): PeriodeRH
    {
        if (!array_key_exists($periodeRH->getId(), $this->cachedResources[PeriodeRHEntity::class])) {
            $this->cachedResources[PeriodeRHEntity::class][$periodeRH->getId()] =
                $this->transformerService->transform($periodeRH, PeriodeRH::class);
        }
        return $this->cachedResources[PeriodeRHEntity::class][$periodeRH->getId()];
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
     * @param UtilisateurEntity $utilisateur
     * @return Utilisateur
     * @throws Exception
     */
    private function getUtilisateurResource(UtilisateurEntity $utilisateur): Utilisateur
    {
        if (!array_key_exists($utilisateur->getId(), $this->cachedResources[UtilisateurEntity::class])) {
            $this->cachedResources[UtilisateurEntity::class][$utilisateur->getId()] =
                $this->transformerService->transform($utilisateur, Utilisateur::class);
        }
        return $this->cachedResources[UtilisateurEntity::class][$utilisateur->getId()];
    }

    /**
     * @param TauxHoraireEntity $tauxHoraire
     * @return TauxHoraire
     * @throws Exception
     */
    private function getTauxHoraireResource(TauxHoraireEntity $tauxHoraire): TauxHoraire
    {
        if (!array_key_exists($tauxHoraire->getId(), $this->cachedResources[TauxHoraireEntity::class])) {
            $this->cachedResources[TauxHoraireEntity::class][$tauxHoraire->getId()] =
                $this->transformerService->transform($tauxHoraire, TauxHoraire::class);
        }
        return $this->cachedResources[TauxHoraireEntity::class][$tauxHoraire->getId()];
    }

}