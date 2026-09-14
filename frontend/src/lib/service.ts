/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { env } from "@/env";

/**
 * Dénominations grammaticales du service d'accompagnement des étudiants.
 *
 * Chaque établissement rattache l'application à une structure dont le nom varie
 * (« service PHASE », « SARE », « Cellule d'aide aux étudiants »…). Ce module dérive
 * toutes les formes nécessaires à partir de trois variables d'environnement :
 *
 * - `REACT_APP_SERVICE` : sigle / nom court, employé seul dans les intitulés composés
 *   (« Accompagnement PHASE », « Renfort PHASE »).
 * - `REACT_APP_SERVICE_DENOMINATION` : groupe nominal complet, sans article, tel qu'il
 *   apparaît au milieu d'une phrase (« service PHASE », « Cellule d'aide aux étudiants »).
 * - `REACT_APP_SERVICE_ARTICLE` : article défini associé (`le`, `la` ou `l'`).
 * - `REACT_APP_SERVICE_DENOMINATION_LONGUE` : dénomination développée, réservée à la
 *   première mention dans un document formel (page RGPD). Optionnelle : par défaut égale
 *   à `REACT_APP_SERVICE_DENOMINATION`.
 *
 * Rétrocompatibilité : sans `REACT_APP_SERVICE_DENOMINATION`, on retombe sur
 * `service <REACT_APP_SERVICE>` avec l'article `le`, soit les textes historiques.
 */

type Article = "le" | "la" | "l'";

/** Préfixes (article défini + prépositions contractées) pour chaque article. */
const PREFIXES: Record<Article, { defini: string; de: string; a: string }> = {
  le: { defini: "le ", de: "du ", a: "au " },
  la: { defini: "la ", de: "de la ", a: "à la " },
  // Élision : pas d'espace après l'apostrophe.
  "l'": { defini: "l'", de: "de l'", a: "à l'" },
};

function resoudreArticle(): Article {
  const brut = env.REACT_APP_SERVICE_ARTICLE?.trim().toLowerCase();
  return brut === "la" || brut === "l'" ? brut : "le";
}

function resoudreDenomination(): string {
  return env.REACT_APP_SERVICE_DENOMINATION?.trim() || `service ${env.REACT_APP_SERVICE}`;
}

function resoudreDenominationLongue(denomination: string): string {
  return env.REACT_APP_SERVICE_DENOMINATION_LONGUE?.trim() || denomination;
}

/** Passe la première lettre en majuscule (pour un début de phrase). */
function capitaliser(valeur: string): string {
  return valeur.charAt(0).toUpperCase() + valeur.slice(1);
}

function construireService() {
  const denomination = resoudreDenomination();
  const denominationLongue = resoudreDenominationLongue(denomination);
  const prefixe = PREFIXES[resoudreArticle()];

  const defini = prefixe.defini + denomination;
  const definiLong = prefixe.defini + denominationLongue;
  const de = prefixe.de + denomination;
  const a = prefixe.a + denomination;

  return {
    /** Sigle / nom court, pour les intitulés composés : `Accompagnement ${service.sigle}`. */
    sigle: env.REACT_APP_SERVICE,
    /** Groupe nominal nu : « service PHASE » / « Cellule d'aide aux étudiants » / « SARE ». */
    denomination,
    /**
     * Dénomination développée, réservée à la première mention dans un document formel
     * (page RGPD). Égale à {@link denomination} si non configurée.
     */
    denominationLongue,
    /** Forme définie : « le service PHASE » / « la Cellule… » / « l'ADEP ». */
    defini,
    /** Forme définie sur la dénomination développée (première mention d'un document formel). */
    definiLong,
    /** Préposition « de » contractée : « du service PHASE » / « de la Cellule… ». */
    de,
    /** Préposition « à » contractée : « au service PHASE » / « à la Cellule… ». */
    a,
    /** Variantes à employer uniquement en début de phrase (ou comme titre). */
    Denomination: capitaliser(denomination),
    DenominationLongue: capitaliser(denominationLongue),
    Defini: capitaliser(defini),
    DefiniLong: capitaliser(definiLong),
    De: capitaliser(de),
    A: capitaliser(a),
  } as const;
}

/** Dénominations grammaticales du service, dérivées de la configuration d'environnement. */
export const service = construireService();
