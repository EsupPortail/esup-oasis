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
import { usePost } from "./UsePost";

// handleApiResponse importe le singleton queryClient (utilisé sur 401) et le logger.
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
const POST_URL = `${BASE_URL}/evenements`;
const FETCH_OPTIONS: RequestInit = { credentials: "include" };

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Options = Parameters<typeof usePost<any>>[3];

function setup(options: Options) {
  const client = createTestQueryClient();
  const utils = renderHookWithProviders(() => usePost(BASE_URL, FETCH_OPTIONS, client, options), {
    queryClient: client,
  });
  return { client, ...utils };
}

describe("usePost", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(notification, "error").mockImplementation(vi.fn());
  });

  it("succès : retourne la ressource créée et appelle onSuccess", async () => {
    server.use(
      http.post(POST_URL, () =>
        HttpResponse.json({ "@id": "/evenements/1", id: 1 }, { status: 201 }),
      ),
    );
    const onSuccess = vi.fn();
    const { result } = setup({ path: "/evenements", onSuccess });

    result.current.mutate({ data: { libelle: "Réunion" } as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toMatchObject({ "@id": "/evenements/1" });
    expect(onSuccess).toHaveBeenCalledOnce();
  });

  it("succès : la ressource créée est placée en cache sous son @id", async () => {
    server.use(http.post(POST_URL, () => HttpResponse.json({ "@id": "/evenements/1", id: 1 })));
    const { result, client } = setup({ path: "/evenements" });

    result.current.mutate({ data: {} as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(client.getQueryData(["/evenements/1"])).toMatchObject({ "@id": "/evenements/1" });
  });

  it("envoie une requête POST avec le corps JSON fourni", async () => {
    let captured: { method: string; body: unknown } | undefined;
    server.use(
      http.post(POST_URL, async ({ request }) => {
        captured = { method: request.method, body: await request.json() };
        return HttpResponse.json({ "@id": "/evenements/1" });
      }),
    );
    const { result } = setup({ path: "/evenements" });

    result.current.mutate({ data: { libelle: "X" } as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(captured?.method).toBe("POST");
    expect(captured?.body).toEqual({ libelle: "X" });
  });

  it("succès : invalide les query keys configurées", async () => {
    server.use(http.post(POST_URL, () => HttpResponse.json({ "@id": "/evenements/1" })));
    const { result, client } = setup({
      path: "/evenements",
      invalidationQueryKeys: ["/evenements"],
    });
    const invalidateSpy = vi.spyOn(client, "invalidateQueries");

    result.current.mutate({ data: {} as never });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(invalidateSpy).toHaveBeenCalled();
  });

  it("erreur HTTP : isError = true et onError appelé", async () => {
    server.use(
      http.post(POST_URL, () =>
        HttpResponse.json({ "hydra:description": "Validation" }, { status: 422 }),
      ),
    );
    const onError = vi.fn();
    const { result } = setup({ path: "/evenements", onError });

    result.current.mutate({ data: {} as never });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(onError).toHaveBeenCalledOnce();
  });
});
