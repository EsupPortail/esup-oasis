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

/** Recharge le module `service` avec la configuration d'environnement courante. */
async function chargerService(env: Record<string, unknown>) {
  Object.keys(mockEnv).forEach((k) => delete mockEnv[k]);
  Object.assign(mockEnv, env);
  vi.resetModules();
  return (await import("./service")).service;
}

beforeEach(() => {
  vi.resetModules();
});

describe("service — rétrocompatibilité", () => {
  it("reconstruit « service <SIGLE> » avec l'article « le » sans configuration dédiée", async () => {
    const service = await chargerService({ REACT_APP_SERVICE: "PHASE" });

    expect(service.sigle).toBe("PHASE");
    expect(service.denomination).toBe("service PHASE");
    expect(service.defini).toBe("le service PHASE");
    expect(service.de).toBe("du service PHASE");
    expect(service.a).toBe("au service PHASE");
  });
});

describe("service — dénomination masculine", () => {
  it("dérive les formes contractées avec « le »", async () => {
    const service = await chargerService({
      REACT_APP_SERVICE: "PHASE",
      REACT_APP_SERVICE_DENOMINATION: "service PHASE",
      REACT_APP_SERVICE_ARTICLE: "le",
    });

    expect(service.defini).toBe("le service PHASE");
    expect(service.de).toBe("du service PHASE");
    expect(service.a).toBe("au service PHASE");
    expect(service.Defini).toBe("Le service PHASE");
    expect(service.De).toBe("Du service PHASE");
  });
});

describe("service — dénomination féminine", () => {
  it("dérive les formes contractées avec « la »", async () => {
    const service = await chargerService({
      REACT_APP_SERVICE: "CAE",
      REACT_APP_SERVICE_DENOMINATION: "Cellule d'accompagnement des étudiants",
      REACT_APP_SERVICE_ARTICLE: "la",
    });

    expect(service.defini).toBe("la Cellule d'accompagnement des étudiants");
    expect(service.de).toBe("de la Cellule d'accompagnement des étudiants");
    expect(service.a).toBe("à la Cellule d'accompagnement des étudiants");
    expect(service.Denomination).toBe("Cellule d'accompagnement des étudiants");
    expect(service.De).toBe("De la Cellule d'accompagnement des étudiants");
  });
});

describe("service — dénomination élidée", () => {
  it("n'ajoute pas d'espace après l'apostrophe", async () => {
    const service = await chargerService({
      REACT_APP_SERVICE: "ADEP",
      REACT_APP_SERVICE_DENOMINATION: "ADEP",
      REACT_APP_SERVICE_ARTICLE: "l'",
    });

    expect(service.defini).toBe("l'ADEP");
    expect(service.de).toBe("de l'ADEP");
    expect(service.a).toBe("à l'ADEP");
    expect(service.Defini).toBe("L'ADEP");
    expect(service.A).toBe("À l'ADEP");
  });
});

describe("service — dénomination longue", () => {
  it("expose la dénomination développée capitalisée quand elle est configurée", async () => {
    const service = await chargerService({
      REACT_APP_SERVICE: "SARE",
      REACT_APP_SERVICE_DENOMINATION: "SARE",
      REACT_APP_SERVICE_ARTICLE: "le",
      REACT_APP_SERVICE_DENOMINATION_LONGUE:
        "service d'Accompagnement à la Réussite des Étudiants (SARE)",
    });

    expect(service.denominationLongue).toBe(
      "service d'Accompagnement à la Réussite des Étudiants (SARE)",
    );
    expect(service.DenominationLongue).toBe(
      "Service d'Accompagnement à la Réussite des Étudiants (SARE)",
    );
    expect(service.definiLong).toBe(
      "le service d'Accompagnement à la Réussite des Étudiants (SARE)",
    );
    expect(service.DefiniLong).toBe(
      "Le service d'Accompagnement à la Réussite des Étudiants (SARE)",
    );
    // Les formes courtes ne sont pas affectées.
    expect(service.defini).toBe("le SARE");
  });

  it("retombe sur la dénomination courte quand elle n'est pas configurée", async () => {
    const service = await chargerService({
      REACT_APP_SERVICE: "PHASE",
      REACT_APP_SERVICE_DENOMINATION: "service PHASE",
    });

    expect(service.denominationLongue).toBe("service PHASE");
    expect(service.DenominationLongue).toBe("Service PHASE");
    expect(service.DefiniLong).toBe(service.Defini);
  });
});

describe("service — valeurs de configuration tolérantes", () => {
  it("ignore la casse et les espaces de REACT_APP_SERVICE_ARTICLE", async () => {
    const service = await chargerService({
      REACT_APP_SERVICE: "SARE",
      REACT_APP_SERVICE_DENOMINATION: " SARE ",
      REACT_APP_SERVICE_ARTICLE: " LE ",
    });

    expect(service.denomination).toBe("SARE");
    expect(service.defini).toBe("le SARE");
    expect(service.de).toBe("du SARE");
  });

  it("retombe sur « le » pour un article non reconnu", async () => {
    const service = await chargerService({
      REACT_APP_SERVICE: "PHASE",
      REACT_APP_SERVICE_DENOMINATION: "service PHASE",
      REACT_APP_SERVICE_ARTICLE: "les",
    });

    expect(service.defini).toBe("le service PHASE");
  });
});
