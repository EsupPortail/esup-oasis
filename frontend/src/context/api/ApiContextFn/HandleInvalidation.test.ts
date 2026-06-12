/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { QueryClient } from "@tanstack/react-query";
import { vi } from "vitest";
import { handleInvalidation } from "./HandleInvalidation";

const mockEnv = { REACT_APP_API_PREFIX: "" };

vi.mock("@/env", () => ({
  get env() {
    return mockEnv;
  },
}));

const makeQuery = (key: unknown) => ({ queryKey: key }) as never;

const makeClient = () => {
  const invalidateQueries = vi.fn().mockResolvedValue(undefined);
  const client = { invalidateQueries } as unknown as QueryClient;
  return { client, invalidateQueries };
};

const getPredicate = (invalidateQueries: ReturnType<typeof vi.fn>) =>
  invalidateQueries.mock.calls[0][0].predicate as (query: never) => boolean;

// ---------------------------------------------------------------------------
// handleInvalidation — appel à invalidateQueries
// ---------------------------------------------------------------------------

describe("handleInvalidation — appel à invalidateQueries", () => {
  it("appelle invalidateQueries une fois sur le client", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(invalidateQueries).toHaveBeenCalledOnce();
  });

  it("appelle onSuccess après résolution de l'invalidation", async () => {
    const { client } = makeClient();
    const onSuccess = vi.fn();
    handleInvalidation(client, ["/evenements"], onSuccess);
    await Promise.resolve();
    expect(onSuccess).toHaveBeenCalledOnce();
  });
});

// ---------------------------------------------------------------------------
// handleInvalidation — prédicat : correspondance startsWith
// ---------------------------------------------------------------------------

describe("handleInvalidation — prédicat : correspondance startsWith", () => {
  it("retourne true pour une clé identique à la queryKey", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/evenements"]))).toBe(true);
  });

  it("retourne true pour une sous-ressource (startsWith)", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/evenements/42"]))).toBe(true);
  });

  it("retourne true pour une clé avec query string (startsWith)", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/evenements?page=1"]))).toBe(true);
  });

  it("retourne false pour une clé qui ne correspond pas", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/utilisateurs"]))).toBe(false);
  });

  it("retourne true si l'une des queryKeys correspond parmi plusieurs", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements", "/utilisateurs"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/utilisateurs/123"]))).toBe(true);
  });

  it("retourne false si aucune queryKey ne correspond parmi plusieurs", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements", "/utilisateurs"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/intervenants"]))).toBe(false);
  });

  it("retourne false pour un tableau de queryKeys vide", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, []);
    expect(getPredicate(invalidateQueries)(makeQuery(["/evenements"]))).toBe(false);
  });
});

// ---------------------------------------------------------------------------
// handleInvalidation — prédicat : cas limites sur queryKey
// ---------------------------------------------------------------------------

describe("handleInvalidation — prédicat : cas limites sur queryKey", () => {
  it("retourne false si queryKey[0] est undefined", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery([undefined]))).toBe(false);
  });

  it("retourne false si queryKey[0] est null", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery([null]))).toBe(false);
  });

  it("retourne false si queryKey est undefined", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(undefined))).toBe(false);
  });
});

// ---------------------------------------------------------------------------
// handleInvalidation — prédicat : matching multi-éléments
// ---------------------------------------------------------------------------

describe("handleInvalidation — prédicat : matching multi-éléments", () => {
  // Clé item telle que produite par useGetItem : [IRI, URL, uid, template].
  const AVIS_ESE_TEMPLATE = "/utilisateurs/{uid}/avis_ese/{id}";

  it("une constante à placeholder matche le template en fin de clé item", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, [AVIS_ESE_TEMPLATE]);
    const itemKey = [
      "/utilisateurs/123/avis_ese/45",
      new URL("https://host/api/utilisateurs/123/avis_ese/45"),
      "uid-1",
      AVIS_ESE_TEMPLATE,
    ];
    expect(getPredicate(invalidateQueries)(makeQuery(itemKey))).toBe(true);
  });

  it("matche un item via son template quand queryKey[0] est undefined (chargé sans IRI)", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, [AVIS_ESE_TEMPLATE]);
    const itemKey = [
      undefined,
      new URL("https://host/api/utilisateurs/123/avis_ese/45"),
      "uid-1",
      AVIS_ESE_TEMPLATE,
    ];
    expect(getPredicate(invalidateQueries)(makeQuery(itemKey))).toBe(true);
  });

  it("une constante sans placeholder matche l'IRI concrète en position 0", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/utilisateurs"]);
    const itemKey = [
      "/utilisateurs/123/avis_ese/45",
      new URL("https://host/api/utilisateurs/123/avis_ese/45"),
      "uid-1",
      AVIS_ESE_TEMPLATE,
    ];
    expect(getPredicate(invalidateQueries)(makeQuery(itemKey))).toBe(true);
  });

  it("n'utilise jamais l'uid comme cible (élément string sans `/`)", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["uid-1"]);
    const itemKey = ["/utilisateurs/123", new URL("https://host/api/utilisateurs/123"), "uid-1"];
    expect(getPredicate(invalidateQueries)(makeQuery(itemKey))).toBe(false);
  });

  it("ignore l'objet URL des collections sans lever d'exception", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    const collectionKey = ["/evenements", new URL("https://host/api/evenements"), "uid-1"];
    expect(() => getPredicate(invalidateQueries)(makeQuery(collectionKey))).not.toThrow();
    expect(getPredicate(invalidateQueries)(makeQuery(collectionKey))).toBe(true);
  });

  it("ne matche pas une IRI vivant dans le href de l'objet URL (https, pas `/`)", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/utilisateurs"]);
    // La collection /evenements filtrée par demandeur : l'IRI utilisateur n'est que dans le href URL.
    const collectionKey = [
      "/evenements",
      new URL("https://host/api/evenements?demandeur=/utilisateurs/123"),
      "uid-1",
    ];
    expect(getPredicate(invalidateQueries)(makeQuery(collectionKey))).toBe(false);
  });

  it("limite assumée : le startsWith par préfixe matche aussi un path frère (/demandes → /demandes_export)", () => {
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/demandes"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/demandes_export"]))).toBe(true);
  });
});

// ---------------------------------------------------------------------------
// handleInvalidation — prédicat : normalisation REACT_APP_API_PREFIX
// ---------------------------------------------------------------------------

describe("handleInvalidation — prédicat : normalisation REACT_APP_API_PREFIX", () => {
  beforeEach(() => {
    mockEnv.REACT_APP_API_PREFIX = "";
  });

  it("matche sans préfixe configuré (comportement par défaut)", () => {
    mockEnv.REACT_APP_API_PREFIX = "";
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/evenements/42"]))).toBe(true);
  });

  it("matche une clé avec préfixe si la queryKey est sans préfixe", () => {
    mockEnv.REACT_APP_API_PREFIX = "/api";
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/api/evenements/42"]))).toBe(true);
  });

  it("matche une clé sans préfixe si la queryKey est sans préfixe et le préfixe est absent de la clé", () => {
    mockEnv.REACT_APP_API_PREFIX = "/api";
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/evenements/42"]))).toBe(true);
  });

  it("ne matche pas une clé avec préfixe différent", () => {
    mockEnv.REACT_APP_API_PREFIX = "/api";
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/api/utilisateurs"]))).toBe(false);
  });

  it("gère un préfixe avec slash final", () => {
    mockEnv.REACT_APP_API_PREFIX = "/api/";
    const { client, invalidateQueries } = makeClient();
    handleInvalidation(client, ["/evenements"]);
    expect(getPredicate(invalidateQueries)(makeQuery(["/api/evenements/42"]))).toBe(false);
  });
});
