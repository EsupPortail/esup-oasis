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
import { useDelete } from "./UseDelete";

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
const DELETE_URL = `${BASE_URL}${IRI}`;
const FETCH_OPTIONS: RequestInit = { credentials: "include" };

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Options = Parameters<typeof useDelete<any>>[3];

function setup(options: Options) {
  const client = createTestQueryClient();
  const utils = renderHookWithProviders(() => useDelete(BASE_URL, FETCH_OPTIONS, client, options), {
    queryClient: client,
  });
  return { client, ...utils };
}

describe("useDelete", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(notification, "error").mockImplementation(vi.fn());
  });

  it("succès (204 No Content) : envoie un DELETE et appelle onSuccess", async () => {
    let method: string | undefined;
    server.use(
      http.delete(DELETE_URL, ({ request }) => {
        method = request.method;
        return new HttpResponse(null, { status: 204 });
      }),
    );
    const onSuccess = vi.fn();
    const { result } = setup({ path: "/evenements/{id}", onSuccess });

    result.current.mutate({ "@id": IRI });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(method).toBe("DELETE");
    expect(onSuccess).toHaveBeenCalledOnce();
  });

  it("succès : invalide les query keys configurées", async () => {
    server.use(http.delete(DELETE_URL, () => new HttpResponse(null, { status: 204 })));
    const { result, client } = setup({
      path: "/evenements/{id}",
      invalidationQueryKeys: ["/evenements"],
    });
    const invalidateSpy = vi.spyOn(client, "invalidateQueries");

    result.current.mutate({ "@id": IRI });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(invalidateSpy).toHaveBeenCalled();
  });

  it("erreur HTTP (409) : isError = true, onSuccess non appelé", async () => {
    server.use(
      http.delete(DELETE_URL, () =>
        HttpResponse.json({ "hydra:description": "Ressource liée" }, { status: 409 }),
      ),
    );
    const onSuccess = vi.fn();
    const { result } = setup({ path: "/evenements/{id}", onSuccess });

    result.current.mutate({ "@id": IRI });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(onSuccess).not.toHaveBeenCalled();
  });
});
