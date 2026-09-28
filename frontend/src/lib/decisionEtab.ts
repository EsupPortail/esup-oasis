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
import { Article, construireFormesGrammaticales, resoudreArticle } from "./denomination";

/**
 * Dénominations grammaticales du libellé de la décision d'établissement.
 *
 * Chaque établissement peut personnaliser le nom donné à ce document (« Décision
 * d'établissement », « PAEH »…). Ce module dérive les formes nécessaires à partir de deux
 * variables d'environnement :
 *
 * - `REACT_APP_DECISION_ETAB_LIB` : groupe nominal complet, sans article, tel qu'il apparaît
 *   au milieu d'une phrase (« décision d'établissement », « PAEH »).
 * - `REACT_APP_DECISION_ETAB_ARTICLE` : article défini associé (`le`, `la` ou `l'`).
 *
 * Rétrocompatibilité : sans `REACT_APP_DECISION_ETAB_ARTICLE`, l'article `la` est utilisé,
 * cohérent avec le libellé par défaut « Décision d'établissement ».
 *
 * `accordE` permet en plus d'accorder les participes passés / adjectifs employés dans l'UI
 * (« envoyé »/« envoyée »…, à composer en suffixe de la forme masculine : `envoyé${accordE}`) :
 * `la` -> `"e"`, `le` -> `""`. Avec `l'` (article élidé, genre non déterminable à partir de la
 * seule lettre), on retombe sur l'écriture inclusive (`"•e"`) plutôt que de deviner un genre.
 */

function accordEDepuisArticle(article: Article): string {
  if (article === "le") return "";
  if (article === "la") return "e";
  return "•e";
}

function construireDecisionEtab() {
  const denomination = env.REACT_APP_DECISION_ETAB_LIB?.trim() || "décision d'établissement";
  const article = resoudreArticle(env.REACT_APP_DECISION_ETAB_ARTICLE || "la");

  return {
    ...construireFormesGrammaticales(denomination, article),
    accordE: accordEDepuisArticle(article),
  };
}

/** Dénominations grammaticales de la décision d'établissement, dérivées de la configuration. */
export const decisionEtab = construireDecisionEtab();
