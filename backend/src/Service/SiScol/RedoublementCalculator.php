<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\SiScol;

/**
 * Détermination du redoublement à partir du compteur natif
 * Apogée `nbr_ins_etp` (nombre d'inscriptions administratives à l'étape).
 *
 * Règle retenue par la maîtrise d'ouvrage : un
 * étudiant est considéré redoublant dès lors que son compteur
 * d'inscriptions à l'étape est strictement supérieur à 1. Cette règle
 * remplace la comparaison naïve du `cod_etp` d'une année sur l'autre,
 * écartée car elle confondait redoublement
 * et contrat pédagogique pluri-annuel.
 *
 * Limite connue, signalée par la DSI : `nbr_ins_etp > 1`
 * ne signifie pas toujours redoublement. Le compteur s'incrémente aussi
 * lors d'un changement d'accréditation ou de régime d'inscription sur la
 * même étape. La garde "cursus aménagé" couvre partiellement ces cas :
 * un étudiant inscrit en cursus aménagé (étalement L3 sur 2 ans, contrat
 * pédagogique pluri-annuel…) verra son compteur dépasser 1 sans être un
 * redoublant, c'est pourquoi la présence d'un code cursus aménagé
 * neutralise le calcul.
 *
 * Service stateless (esprit NiveauExtractor) : sans état ni dépendance,
 * instanciable à la volée et testable unitairement.
 */
class RedoublementCalculator
{
    public function estRedoublant(?int $nombreInscriptionsEtape, ?string $codeCursusAmenage): bool
    {
        // Garde cursus aménagé : un cursus aménagé explique un compteur
        // d'inscriptions > 1 qui n'est pas un redoublement.
        if ($codeCursusAmenage !== null && trim($codeCursusAmenage) !== '') {
            return false;
        }

        return ($nombreInscriptionsEtape ?? 0) > 1;
    }
}
