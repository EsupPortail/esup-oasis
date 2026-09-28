/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { beforeEach, describe, expect, it, vi } from "vitest";

const { mockEnv } = vi.hoisted(() => ({
  mockEnv: {} as Record<string, unknown>,
}));

vi.mock("@/env", () => ({
  get env() {
    return mockEnv;
  },
}));

/** Recharge le module `decisionEtab` avec la configuration d'environnement courante. */
async function chargerDecisionEtab(env: Record<string, unknown>) {
  Object.keys(mockEnv).forEach((k) => delete mockEnv[k]);
  Object.assign(mockEnv, env);
  vi.resetModules();
  return (await import("./decisionEtab")).decisionEtab;
}

beforeEach(() => {
  vi.resetModules();
});

describe("decisionEtab — rétrocompatibilité", () => {
  it("utilise l'article « la » quand REACT_APP_DECISION_ETAB_ARTICLE n'est pas configurée", async () => {
    const decisionEtab = await chargerDecisionEtab({
      REACT_APP_DECISION_ETAB_LIB: "Décision d'établissement",
    });

    expect(decisionEtab.denomination).toBe("Décision d'établissement");
    expect(decisionEtab.defini).toBe("la Décision d'établissement");
    expect(decisionEtab.de).toBe("de la Décision d'établissement");
    expect(decisionEtab.a).toBe("à la Décision d'établissement");
    expect(decisionEtab.accordE).toBe("e");
  });
});

describe("decisionEtab — libellé masculin", () => {
  it("dérive les formes contractées avec « le »", async () => {
    const decisionEtab = await chargerDecisionEtab({
      REACT_APP_DECISION_ETAB_LIB: "PAEH",
      REACT_APP_DECISION_ETAB_ARTICLE: "le",
    });

    expect(decisionEtab.defini).toBe("le PAEH");
    expect(decisionEtab.de).toBe("du PAEH");
    expect(decisionEtab.a).toBe("au PAEH");
    expect(decisionEtab.Defini).toBe("Le PAEH");
    expect(decisionEtab.De).toBe("Du PAEH");
    expect(decisionEtab.accordE).toBe("");
  });
});

describe("decisionEtab — libellé élidé", () => {
  it("n'ajoute pas d'espace après l'apostrophe, et retombe sur l'écriture inclusive (genre indéterminable)", async () => {
    const decisionEtab = await chargerDecisionEtab({
      REACT_APP_DECISION_ETAB_LIB: "ADEP",
      REACT_APP_DECISION_ETAB_ARTICLE: "l'",
    });

    expect(decisionEtab.defini).toBe("l'ADEP");
    expect(decisionEtab.de).toBe("de l'ADEP");
    expect(decisionEtab.a).toBe("à l'ADEP");
    expect(decisionEtab.Defini).toBe("L'ADEP");
    expect(decisionEtab.accordE).toBe("•e");
  });
});

describe("decisionEtab — valeurs de configuration tolérantes", () => {
  it("ignore la casse et les espaces de REACT_APP_DECISION_ETAB_ARTICLE", async () => {
    const decisionEtab = await chargerDecisionEtab({
      REACT_APP_DECISION_ETAB_LIB: " PAEH ",
      REACT_APP_DECISION_ETAB_ARTICLE: " LE ",
    });

    expect(decisionEtab.denomination).toBe("PAEH");
    expect(decisionEtab.defini).toBe("le PAEH");
    expect(decisionEtab.de).toBe("du PAEH");
  });

  it("retombe sur « le » pour un article non reconnu (indépendamment du défaut « la »)", async () => {
    const decisionEtab = await chargerDecisionEtab({
      REACT_APP_DECISION_ETAB_LIB: "Décision d'établissement",
      REACT_APP_DECISION_ETAB_ARTICLE: "les",
    });

    expect(decisionEtab.defini).toBe("le Décision d'établissement");
    expect(decisionEtab.accordE).toBe("");
  });
});
