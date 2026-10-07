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
import { renderHookWithProviders, hydraCollection } from "@/test";
import { useGetCollection } from "./UseGetCollection";

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
const API_URL = `${BASE_URL}/evenements`;
const FETCH_OPTIONS: RequestInit = { credentials: "include" };

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Options = Parameters<typeof useGetCollection<any>>[2];

function setup(options: Options) {
  return renderHookWithProviders(() => useGetCollection(BASE_URL, FETCH_OPTIONS, options));
}

describe("useGetCollection", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(notification, "error").mockImplementation(vi.fn());
  });

  it("succès : expose items et totalItems issus de la réponse Hydra", async () => {
    server.use(
      http.get(API_URL, () =>
        HttpResponse.json(
          hydraCollection([{ "@id": "/evenements/1" }, { "@id": "/evenements/2" }], 2),
        ),
      ),
    );
    const { result } = setup({ path: "/evenements" });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.items).toHaveLength(2);
    expect(result.current.data?.totalItems).toBe(2);
  });

  it("sérialise les query params (triés) dans l'URL de la requête", async () => {
    let requestUrl: URL | undefined;
    server.use(
      http.get(API_URL, ({ request }) => {
        requestUrl = new URL(request.url);
        return HttpResponse.json(hydraCollection([], 0));
      }),
    );
    const { result } = setup({
      path: "/evenements",
      query: { "fin[before]": "2026-06-30", "debut[after]": "2026-06-01" } as never,
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(requestUrl?.searchParams.get("debut[after]")).toBe("2026-06-01");
    expect(requestUrl?.searchParams.get("fin[before]")).toBe("2026-06-30");
  });

  it("enabled = false : aucun fetch, requête inactive", () => {
    const { result } = setup({ path: "/evenements", enabled: false });

    expect(result.current.isLoading).toBe(false);
    expect(result.current.fetchStatus).toBe("idle");
    expect(result.current.data).toBeUndefined();
  });

  it("erreur HTTP : isError = true, onError appelé", async () => {
    server.use(
      http.get(API_URL, () =>
        HttpResponse.json({ "hydra:description": "Interdit" }, { status: 403 }),
      ),
    );
    const onError = vi.fn();
    const { result } = setup({ path: "/evenements", onError });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(onError).toHaveBeenCalled();
    // onError fourni → la notification globale est court-circuitée.
    expect(notification.error).not.toHaveBeenCalled();
  });
});
