/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { env } from "@/env";

/**
 * Nombre maximum d'éléments par page.
 * Utilisé pour récupérer l'intégralité des éléments d'une collection.
 *
 * @constant {number} NB_MAX_ITEMS_PER_PAGE
 * @example 9999
 */
export const NB_MAX_ITEMS_PER_PAGE = 9999;

/**
 * Id du type d'évènements "Renfort au service"
 * @example "/types_evenements/-1".
 *
 * @constant {string} TYPE_EVENEMENT_RENFORT
 */
export const TYPE_EVENEMENT_RENFORT = "/types_evenements/-1";

/**
 * Id du profil bénéficiaire à déterminer
 *
 * @example "/profils/-1"
 * @constant {string}
 */
export const BENEFICIAIRE_PROFIL_A_DETERMINER = "/profils/-1";

/**
 * Id du paramètre "Coefficient de charges"
 *
 * @example "/parametres/COEFFICIENT_CHARGES"
 * @constant {string}
 */
export const PARAMETRE_COEF_COUT_CHARGE = "/parametres/COEFFICIENT_CHARGES";

/**
 * Taille max des fichiers en upload, en Mo.
 * Configurable via la variable d'environnement REACT_APP_MAX_FILE_SIZE (10 Mo par défaut).
 *
 * @example 10
 * @constant {number}
 */
export const MAX_FILE_SIZE =
  Number(env.REACT_APP_MAX_FILE_SIZE) > 0 ? Number(env.REACT_APP_MAX_FILE_SIZE) : 10;

/**
 * Couleurs de l'application : récupérées depuis les variables d'environnement.
 *
 * REACT_APP_PRIMARY_COLOR="#1d71b8"
 * REACT_APP_PRIMARY_CONTRAST_COLOR="#000000"
 * REACT_APP_PRIMARY_LIGHT_COLOR="#e1f5ff"
 *
 * REACT_APP_SECONDARY_COLOR="#ffe082"
 * REACT_APP_SECONDARY_LIGHT_COLOR="#fff8e1"
 * REACT_APP_SECONDARY_CONTRAST_COLOR="#cccccc"
 *
 * REACT_APP_ERROR_COLOR="#ff4d4f"
 * REACT_APP_ERROR_LIGHT_COLOR="#fff0f0"
 * REACT_APP_WARNING_COLOR="#EF7D03"
 * REACT_APP_WARNING_LIGHT_COLOR="#fff4e8"
 * REACT_APP_SUCCESS_COLOR="#52c41a"
 * REACT_APP_SUCCESS_LIGHT_COLOR="#b0f091"
 *
 */
export const APP_PRIMARY_COLOR = env.REACT_APP_PRIMARY_COLOR as string;
export const APP_PRIMARY_CONTRAST_COLOR = env.REACT_APP_PRIMARY_CONTRAST_COLOR as string | null;
export const APP_PRIMARY_LIGHT_COLOR = env.REACT_APP_PRIMARY_LIGHT_COLOR as string | null;

export const APP_SECONDARY_COLOR = env.REACT_APP_SECONDARY_COLOR || "#ffe082";
export const APP_SECONDARY_LIGHT_COLOR = env.REACT_APP_SECONDARY_LIGHT_COLOR as string | null;
export const APP_SECONDARY_CONTRAST_COLOR = env.REACT_APP_SECONDARY_CONTRAST_COLOR as string | null;

export const APP_ERROR_COLOR = env.REACT_APP_ERROR_COLOR || "#ff4d4f";
export const APP_ERROR_LIGHT_COLOR = env.REACT_APP_ERROR_LIGHT_COLOR as string | null;
export const APP_WARNING_COLOR = env.REACT_APP_WARNING_COLOR || "#EF7D03";
export const APP_WARNING_LIGHT_COLOR = env.REACT_APP_WARNING_LIGHT_COLOR as string | null;
export const APP_SUCCESS_COLOR = env.REACT_APP_SUCCESS_COLOR || "#52c41a";
export const APP_SUCCESS_LIGHT_COLOR = env.REACT_APP_SUCCESS_LIGHT_COLOR as string | null;
