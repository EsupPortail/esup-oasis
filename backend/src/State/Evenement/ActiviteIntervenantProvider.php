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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ActiviteIntervenant;
use App\ApiResource\ActiviteUtilisateur;
use App\ApiResource\Campus;
use App\ApiResource\TauxHoraire;
use App\ApiResource\TypeEvenement;
use App\ApiResource\Utilisateur;
use App\Entity\Evenement;
use App\Entity\InterventionForfait;
use App\State\TransformerService;
use Exception;

readonly class ActiviteIntervenantProvider implements ProviderInterface
{
    public function __construct(private CollectionProvider $provider,
                                private TransformerService $transformerService)
    {

    }


    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return object|array|object[]|null
     * @throws Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $evenementsOperation = (clone $operation)->withClass(Evenement::class)
            ->withPaginationEnabled(false);

        $interventionsOperation = (clone $operation)->withClass(InterventionForfait::class)
            ->withPaginationEnabled(false);

        $context['filters']['exists']['intervenant'] = true;

        /**
         * @var InterventionForfait[] $interventions
         */
        $interventions = $this->provider->provide($interventionsOperation, $uriVariables, $context);

        /**
         * @var Evenement[] $evenements
         */
        $context['filters']['exists']['dateAnnulation'] = false; //on ne tient pas compte des événements annulés !
        $evenements = $this->provider->provide($evenementsOperation, $uriVariables, $context);

        $results = [];
        foreach ([...$evenements, ...$interventions] as $item) {
            $tauxEntity = $item->getType()->getTauxHoraireActifPourDate(
                match (true) {
                    $item instanceof Evenement => $item->getDebut(),
                    $item instanceof InterventionForfait => $item->getPeriode()->getFin(),
                    default => null
                }
            );
            if (null !== $tauxEntity) {
                $taux = $this->transformerService->transform(entity: $tauxEntity, to: TauxHoraire::class);
            }
            $campusId = $item instanceof InterventionForfait ? 'undefined' : $item->getCampus()->getId();
            $key = $item->getIntervenant()->getId() . '#' . $campusId ?? 'undefined' . '#' . $item->getType()->getId() . '#' . ($taux->id ?? 'undefined');
            if (!array_key_exists($key, $results)) {
                $results[$key] = new ActiviteIntervenant(
                    id: $key,
                    utilisateur: $this->transformerService->transform(
                        entity: $item->getIntervenant()->getUtilisateur(),
                        to: Utilisateur::class),
                    campus: $item instanceof Evenement ? $this->transformerService->transform(entity: $item->getCampus(), to: Campus::class) : null,
                    type: $this->transformerService->transform(entity: $item->getType(), to: TypeEvenement::class),
                    tauxHoraire: $taux ?? null
                );
            }
            $results[$key]->nbEvenements++;
            $results[$key]->nbHeures = bcadd($results[$key]->nbHeures, $item->getDureeEnHeures());
        }

        //On trie le résultat
        usort($results, ActiviteUtilisateur::compare(...));

        //pagination? Peu probable que ce soit nécessaire...
        return $results;
    }
}