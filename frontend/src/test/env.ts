/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

/**
 * Configuration runtime (`@/env`) factorisée pour les tests.
 *
 * `setupTests.ts` mocke globalement `@/env` avec {@link TEST_ENV}, ce qui évite les
 * `REACT_APP_*` undefined (cf. commit `de734e6`) et la répétition d'un
 * `vi.mock("@/env", …)` dans chaque fichier. Un test qui a besoin de valeurs
 * spécifiques surcharge localement via {@link makeTestEnv}.
 */

/** Valeurs d'environnement par défaut, suffisantes pour la majorité des tests. */
export const TEST_ENV = {
  MODE: "development",

  REACT_APP_TITRE: "Oasis Test",
  REACT_APP_ETABLISSEMENT: "Université de Test",
  REACT_APP_ETABLISSEMENT_ARTICLE: "l'",
  REACT_APP_ETABLISSEMENT_ABV: "UT",
  REACT_APP_ETABLISSEMENT_ABV_ARTICLE: "l'",
  REACT_APP_ETABLISSEMENT_URL: null,
  REACT_APP_SERVICE: "SAE",
  REACT_APP_SERVICE_DENOMINATION: null,
  REACT_APP_SERVICE_DENOMINATION_LONGUE: null,
  REACT_APP_SERVICE_ARTICLE: null,
  REACT_APP_EMAIL_SERVICE: "sae@test.fr",
  REACT_APP_URL_SERVICE: null,
  REACT_APP_ESPACE_SANTE: null,
  REACT_APP_ESPACE_SANTE_ABV: null,
  REACT_APP_ADRESSE_DPD: null,
  REACT_APP_EMAIL_DPD: null,
  REACT_APP_INFOS_AUTH: null,
  REACT_APP_LOGO: null,
  REACT_APP_LOGO_DARK: null,

  REACT_APP_PHOTO: null,
  REACT_APP_PHOTO_DEMO: null,

  REACT_APP_NOM_SERVICE_SYNCHRO: null,
  REACT_APP_URL_SERVICE_SYNCHRO: null,
  REACT_APP_GUIDE_SERVICE_SYNCHRO: null,

  REACT_APP_VERSION: "0.0.0-test",

  REACT_APP_VISITE_GUIDEE: null,
  REACT_APP_MSG_ACCUEIL: null,
  REACT_APP_DARKMODE: false,
  REACT_APP_GERER_DEMANDES: true,
  REACT_APP_MAX_FILE_SIZE: null,

  REACT_APP_PRIMARY_COLOR: "#0063AF",
  REACT_APP_PRIMARY_CONTRAST_COLOR: null,
  REACT_APP_PRIMARY_LIGHT_COLOR: null,
  REACT_APP_SECONDARY_COLOR: null,
  REACT_APP_SECONDARY_LIGHT_COLOR: null,
  REACT_APP_SECONDARY_CONTRAST_COLOR: null,
  REACT_APP_ERROR_COLOR: null,
  REACT_APP_ERROR_LIGHT_COLOR: null,
  REACT_APP_WARNING_COLOR: null,
  REACT_APP_WARNING_LIGHT_COLOR: null,
  REACT_APP_SUCCESS_COLOR: null,
  REACT_APP_SUCCESS_LIGHT_COLOR: null,

  REACT_APP_API: "http://api.test",
  REACT_APP_API_PREFIX: "",
  REACT_APP_OAUTH_PROVIDER: "http://oauth.test",
  REACT_APP_OAUTH_CLIENT_ID: "test-client",
  REACT_APP_FRONTEND: "http://localhost:3000",
  REACT_APP_ENVIRONMENT: "test",
} as const;

/** Type de l'objet `env` réel, sans tirer d'import lourd. */
export type TestEnv = Record<string, unknown>;

/**
 * Construit un objet env de test à partir de {@link TEST_ENV} + surcharges.
 *
 * @example
 * vi.mock("@/env", () => ({ env: makeTestEnv({ REACT_APP_ENVIRONMENT: "production" }) }));
 */
export function makeTestEnv(overrides: Record<string, unknown> = {}): TestEnv {
  return { ...TEST_ENV, ...overrides };
}
