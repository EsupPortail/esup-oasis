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

namespace App\Serializer;

use App\ApiResource\BilanFinancier;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;


class BilanFinancierNormalizer implements NormalizerInterface
{

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if ($format === 'customcsv') {
            return $this->toArray($object);
        }
        return [$object];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, ?array $context = null): bool
    {
        if (!$data instanceof BilanFinancier || !in_array($format, ['customcsv'])) {
            return false;
        }

        return true;
    }

    function getSupportedTypes(?string $format): array
    {
        if (!in_array($format, ['customcsv'])) {
            return [];
        }

        return [BilanFinancier::class => false];
    }

    private function toArray(BilanFinancier $bilan): array
    {
        $data = [];
        /**
         * Entêtes
         */
        $data[] = ["Bilan financier"];
        $data[] = ["Période", "Du " . $bilan->debut->format('d/m/Y') . " au " . $bilan->fin->format('d/m/Y')];
        $data[] = [];
        $data[] = ['Nom', 'Prénom', 'Email', 'Période', 'Type', 'Taux horaire', 'Heures', 'Montant brut', 'Coeff charges', 'Montant chargé'];


        foreach ($bilan->intervenants as $intervenant) {
            $lineStart = [$intervenant->intervenant->nom, $intervenant->intervenant->prenom, $intervenant->intervenant->email];
            foreach ($intervenant->activitesParPeriode as $activiteBilanFinancier) {
                $data[] = [...$lineStart,
                    $activiteBilanFinancier->periode->debut->format('d/m/y') . ' au ' . $activiteBilanFinancier->periode->fin->format('d/m/y'),
                    $activiteBilanFinancier->typeEvenement->libelle,
                    str_replace('.', ',', $activiteBilanFinancier->tauxHoraire?->montant ?? 0),
                    str_replace('.', ',', $activiteBilanFinancier->getNbHeures()),
                    str_replace('.', ',', $activiteBilanFinancier->getMontantBrut()),
                    str_replace('.', ',', $activiteBilanFinancier->coeffCharges),
                    str_replace('.', ',', $activiteBilanFinancier->getMontantBrutCharge()),
                ];
            }
        }

        return $data;
    }
}