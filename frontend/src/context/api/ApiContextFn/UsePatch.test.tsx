/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { notification } from "antd";
import { server } from "@/mocks/server";
import { createTestQueryClient, renderHookWithProviders } from "@/test";
import { usePatch } from "./UsePatch";

vi.mock("@/queryClient", () => ({
  queryClient: { clear: vi.fn(), invalidateQueries: vi.fn() },
}));
vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useNavigate: () => vi.fn() };
});
vi.mock("@/auth/AuthProvider", () => ({
  useAuth: () => ({ user: { uid: "test-user" }, signOut: vi.fn() }),
}));
vi.mock("@utils/logger", () => ({ logger: { error: vi.fn() } }));

const BASE_URL = "http://api.test";
const IRI = "/evenements/1";
const PATCH_URL = `${BASE_URL}${IRI}`;
const FETCH_OPTIONS: RequestInit = { credentials: "include" };

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Options = Parameters<typeof usePatch<any>>[3];

function setup(options: Options) {
  const client = createTestQueryClient();
  const utils = renderHookWithProviders(() => usePatch(BASE_URL, FETCH_OPTIONS, client, options), {
    queryClient: client,
  });
  return { client, ...utils };
}

describe("usePatch", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(notification, "error").mockImplementation(vi.fn());
  });

  it("envoie un PATCH avec le Content-Type merge-patch+json (spécif. API Platform)", async () => {
    let contentType: string | null = null;
    let body: unknown;
    server.use(
      http.patch(PATCH_URL, async ({ request }) => {
        contentType = request.headers.get("Content-Type");
        body = await request.json();
        return HttpResponse.json({ "@id": IRI, libelle: "Maj" });
      }),
    );
    const { result } = setup({ path: "/evenements/{id}" });

    result.current.mutate({ "@id": IRI, data: { libelle: "Maj" } as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(contentType).toBe("application/merge-patch+json");
    expect(body).toEqual({ libelle: "Maj" });
  });

  it("succès : met à jour le cache existant sous l'IRI de la ressource", async () => {
    server.use(http.patch(PATCH_URL, () => HttpResponse.json({ "@id": IRI, libelle: "Maj" })));
    const onSuccess = vi.fn();
    const { result, client } = setup({ path: "/evenements/{id}", onSuccess });
    // setQueriesData ne met à jour que les entrées existantes : on pré-remplit le cache.
    client.setQueryData([IRI], { "@id": IRI, libelle: "Ancien" });

    result.current.mutate({ "@id": IRI, data: {} as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(client.getQueryData([IRI])).toMatchObject({ libelle: "Maj" });
    expect(onSuccess).toHaveBeenCalledOnce();
  });

  it("succès : invalide les query keys configurées", async () => {
    server.use(http.patch(PATCH_URL, () => HttpResponse.json({ "@id": IRI })));
    const { result, client } = setup({
      path: "/evenements/{id}",
      invalidationQueryKeys: ["/evenements"],
    });
    const invalidateSpy = vi.spyOn(client, "invalidateQueries");

    result.current.mutate({ "@id": IRI, data: {} as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(invalidateSpy).toHaveBeenCalled();
  });

  it("erreur HTTP : isError = true et onError appelé", async () => {
    server.use(
      http.patch(PATCH_URL, () =>
        HttpResponse.json({ "hydra:description": "Invalide" }, { status: 422 }),
      ),
    );
    const onError = vi.fn();
    const { result } = setup({ path: "/evenements/{id}", onError });

    result.current.mutate({ "@id": IRI, data: {} as never });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(onError).toHaveBeenCalled();
  });
});
