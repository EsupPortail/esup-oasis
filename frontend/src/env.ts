/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

type EnvType = {
  MODE: "development" | "production";

  // Variables liées à l'établissement et au service d'accompagnement des étudiants
  REACT_APP_TITRE: string;
  REACT_APP_ETABLISSEMENT: string;
  REACT_APP_ETABLISSEMENT_ARTICLE: string;
  REACT_APP_ETABLISSEMENT_ABV: string;
  REACT_APP_ETABLISSEMENT_ABV_ARTICLE: string;
  REACT_APP_ETABLISSEMENT_URL: string | null;
  REACT_APP_SERVICE: string;
  REACT_APP_EMAIL_SERVICE: string;
  REACT_APP_URL_SERVICE: string | null;
  REACT_APP_ESPACE_SANTE: string | null;
  REACT_APP_ESPACE_SANTE_ABV: string | null;
  REACT_APP_ADRESSE_DPD: string | null;
  REACT_APP_EMAIL_DPD: string | null;
  REACT_APP_INFOS_AUTH: string | null;
  REACT_APP_LOGO: string | null;
  REACT_APP_LOGO_DARK: string | null;

  // Variables pour l'affichage des photos des étudiants
  REACT_APP_PHOTO: string | null;
  REACT_APP_PHOTO_DEMO: string | null;

  // Variables pour le service de synchronisation des événements
  REACT_APP_NOM_SERVICE_SYNCHRO: string | null;
  REACT_APP_URL_SERVICE_SYNCHRO: string | null;
  REACT_APP_GUIDE_SERVICE_SYNCHRO: string | null;

  // Variable pour la version de l'application
  REACT_APP_VERSION: string;

  // Autres variables de l'application
  REACT_APP_VISITE_GUIDEE: string | null;
  REACT_APP_MSG_ACCUEIL: string | null;
  REACT_APP_DARKMODE: boolean;
  REACT_APP_GERER_DEMANDES: boolean;
  REACT_APP_MAX_FILE_SIZE: string | null;

  // Variables liées aux couleurs de l'application
  REACT_APP_PRIMARY_COLOR: string;
  REACT_APP_PRIMARY_CONTRAST_COLOR: string | null;
  REACT_APP_PRIMARY_LIGHT_COLOR: string | null;
  REACT_APP_SECONDARY_COLOR: string | null;
  REACT_APP_SECONDARY_LIGHT_COLOR: string | null;
  REACT_APP_SECONDARY_CONTRAST_COLOR: string | null;
  REACT_APP_ERROR_COLOR: string | null;
  REACT_APP_ERROR_LIGHT_COLOR: string | null;
  REACT_APP_WARNING_COLOR: string | null;
  REACT_APP_WARNING_LIGHT_COLOR: string | null;
  REACT_APP_SUCCESS_COLOR: string | null;
  REACT_APP_SUCCESS_LIGHT_COLOR: string | null;

  // Variables liées à l'environnement d'exécution
  REACT_APP_API: string;
  REACT_APP_API_PREFIX: string;
  REACT_APP_OAUTH_PROVIDER: string;
  REACT_APP_OAUTH_CLIENT_ID: string;
  REACT_APP_FRONTEND: string;
  REACT_APP_ENVIRONMENT: string;
};

declare global {
  interface Window {
    env: EnvType;
  }
}

function toFeatureEnabled(
  value1: boolean | string | undefined,
  value2: boolean | string | undefined,
  defaultValue: boolean,
): boolean {
  if (value1) {
    return typeof value1 === "boolean" ? value1 : value1 === "true";
  }
  if (value2) {
    return typeof value2 === "boolean" ? value2 : value2 === "true";
  }
  return defaultValue;
}

export const env: EnvType = {
  ...{ REACT_APP_API_PREFIX: "" },
  ...(import.meta.env as unknown as EnvType),
  ...(window.env as unknown as EnvType),
  // À partir de la 2.4, la version de l'application est récupérée depuis le build.
  // On empêche que la variable soit écrasée par une précédente configuration.
  REACT_APP_VERSION: (import.meta.env as unknown as EnvType).REACT_APP_VERSION,
  REACT_APP_GERER_DEMANDES: toFeatureEnabled(
    window.env.REACT_APP_GERER_DEMANDES,
    import.meta.env.REACT_APP_GERER_DEMANDES,
    true,
  ),
  REACT_APP_DARKMODE: toFeatureEnabled(
    window.env.REACT_APP_DARKMODE,
    import.meta.env.REACT_APP_DARKMODE,
    false,
  ),
};

const REQUIRED_ENV_VARS: (keyof EnvType)[] = [
  "REACT_APP_API",
  "REACT_APP_OAUTH_PROVIDER",
  "REACT_APP_OAUTH_CLIENT_ID",
  "REACT_APP_FRONTEND",
];

export function validateEnv(): void {
  const missing = REQUIRED_ENV_VARS.filter((key) => !env[key]);
  if (missing.length > 0) {
    throw new Error(`Variables d'environnement manquantes : ${missing.join(", ")}`);
  }
}
