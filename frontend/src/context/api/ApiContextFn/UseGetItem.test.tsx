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
import { renderHookWithProviders } from "@/test";
import { useGetItem } from "./UseGetItem";

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
const ITEM_URL = `${BASE_URL}/evenements/1`;
const FETCH_OPTIONS: RequestInit = { credentials: "include" };

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Options = Parameters<typeof useGetItem<any>>[2];

function setup(options: Options) {
  return renderHookWithProviders(() => useGetItem(BASE_URL, FETCH_OPTIONS, options));
}

describe("useGetItem", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(notification, "error").mockImplementation(vi.fn());
  });

  it("succès : retourne la ressource demandée", async () => {
    server.use(
      http.get(ITEM_URL, () => HttpResponse.json({ "@id": "/evenements/1", libelle: "Réunion" })),
    );
    const { result } = setup({ path: "/evenements/{id}", url: "/evenements/1" });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toMatchObject({ "@id": "/evenements/1", libelle: "Réunion" });
  });

  it("404 : isError = true, onError appelé, notification globale court-circuitée", async () => {
    server.use(
      http.get(ITEM_URL, () =>
        HttpResponse.json({ "hydra:description": "Introuvable" }, { status: 404 }),
      ),
    );
    const onError = vi.fn();
    const { result } = setup({ path: "/evenements/{id}", url: "/evenements/1", onError });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.data).toBeUndefined();
    expect(onError).toHaveBeenCalled();
    expect(notification.error).not.toHaveBeenCalled();
  });

  it("enabled = false : aucun fetch, requête inactive", () => {
    const { result } = setup({ path: "/evenements/{id}", url: "/evenements/1", enabled: false });

    expect(result.current.isLoading).toBe(false);
    expect(result.current.fetchStatus).toBe("idle");
    expect(result.current.data).toBeUndefined();
  });

  it("sérialise les query params dans l'URL", async () => {
    let requestUrl: URL | undefined;
    server.use(
      http.get(ITEM_URL, ({ request }) => {
        requestUrl = new URL(request.url);
        return HttpResponse.json({ "@id": "/evenements/1" });
      }),
    );
    const { result } = setup({
      path: "/evenements/{id}",
      url: "/evenements/1",
      query: { format: "court" } as never,
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(requestUrl?.searchParams.get("format")).toBe("court");
  });
});
