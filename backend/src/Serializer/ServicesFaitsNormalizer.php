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

use App\ApiResource\ServicesFaits;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;


class ServicesFaitsNormalizer implements NormalizerInterface
{

    /**
     * @param ServicesFaits $object
     * @param string|null   $format
     * @param array         $context
     * @return array
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if ($format === 'customcsv') {
            return $this->toArray($object);
        }
        return [$object];
    }

    protected function toArray($object): array
    {
        $data = [];

        /**
         * Entêtes
         */
        $data[] = ["TABLEAU SERVICES FAITS"];
        $data[] = ["Période", "Du " . $object->periode->debut->format('d/m/Y') . " au " . $object->periode->fin->format('d/m/Y')];
        $data[] = [$object->structure];
        $data[] = [];
        $data[] = ['Email', 'Nom', 'Prénom', 'Catégorie', 'Nombre heures', 'taux horaire'];

        foreach ($object->lignes as $ligne) {
            $data[] = [
                $ligne->intervenant->email,
                $ligne->intervenant->nom,
                $ligne->intervenant->prenom,
                $ligne->type->libelle,
                str_replace('.', ',', $ligne->nbHeures),
                str_replace('.', ',', $ligne->tauxHoraire->montant),
            ];
        }

        return $data;
    }

    /**
     * @param mixed       $data
     * @param string|null $format
     * @param array       $context
     * @return bool
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!$data instanceof ServicesFaits || !in_array($format, ['customcsv', 'pdf'])) {
            return false;
        }

        //Période validée ?
        if (!$data->periode->envoyee) {
            return false;
        }

        return true;
    }

    function getSupportedTypes(?string $format): array
    {
        if (!in_array($format, ['customcsv', 'pdf'])) {
            return [];
        }

        return [ServicesFaits::class => false];
    }
}