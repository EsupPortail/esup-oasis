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
import { usePut } from "./UsePut";

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
const PUT_URL = `${BASE_URL}${IRI}`;
const FETCH_OPTIONS: RequestInit = { credentials: "include" };

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Options = Parameters<typeof usePut<any>>[3];

function setup(options: Options) {
  const client = createTestQueryClient();
  const utils = renderHookWithProviders(() => usePut(BASE_URL, FETCH_OPTIONS, client, options), {
    queryClient: client,
  });
  return { client, ...utils };
}

describe("usePut", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(notification, "error").mockImplementation(vi.fn());
  });

  it("succès : retourne la ressource mise à jour et appelle onSuccess", async () => {
    server.use(http.put(PUT_URL, () => HttpResponse.json({ "@id": IRI, libelle: "Maj" })));
    const onSuccess = vi.fn();
    const { result } = setup({ path: "/evenements/{id}", onSuccess });

    result.current.mutate({ "@id": IRI, data: { libelle: "Maj" } as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toMatchObject({ libelle: "Maj" });
    expect(onSuccess).toHaveBeenCalledOnce();
  });

  it("envoie une requête PUT à l'IRI avec le corps complet", async () => {
    let captured: { method: string; body: unknown } | undefined;
    server.use(
      http.put(PUT_URL, async ({ request }) => {
        captured = { method: request.method, body: await request.json() };
        return HttpResponse.json({ "@id": IRI });
      }),
    );
    const { result } = setup({ path: "/evenements/{id}" });

    result.current.mutate({ "@id": IRI, data: { libelle: "Y" } as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(captured?.method).toBe("PUT");
    expect(captured?.body).toEqual({ libelle: "Y" });
  });

  it("succès : invalide les query keys configurées", async () => {
    server.use(http.put(PUT_URL, () => HttpResponse.json({ "@id": IRI })));
    const { result, client } = setup({
      path: "/evenements/{id}",
      invalidationQueryKeys: ["/evenements"],
    });
    const invalidateSpy = vi.spyOn(client, "invalidateQueries");

    result.current.mutate({ "@id": IRI, data: {} as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(invalidateSpy).toHaveBeenCalled();
  });

  it("erreur HTTP : onError reçoit la notification, pas d'affichage global", async () => {
    server.use(
      http.put(PUT_URL, () =>
        HttpResponse.json({ "hydra:description": "Conflit" }, { status: 409 }),
      ),
    );
    const onError = vi.fn();
    const { result } = setup({ path: "/evenements/{id}", onError });

    result.current.mutate({ "@id": IRI, data: {} as never });

    await waitFor(() => expect(result.current.isError).toBe(true));
    // usePut transmet onError à handleApiResponse → la notification globale est court-circuitée.
    expect(onError).toHaveBeenCalled();
    expect(notification.error).not.toHaveBeenCalled();
  });
});
