import { waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { server } from "@/mocks/server";
import { renderHookWithProviders, hydraCollection } from "@/test";
import { useGetFullCollection } from "./UseGetFullCollection";

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

const BASE_URL = "http://api.test";
const API_URL = `${BASE_URL}/utilisateurs`;
const FETCH_OPTIONS: RequestInit = { credentials: "include" };

describe("useGetFullCollection", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("collection vide : isSuccess et data.items = []", async () => {
    server.use(http.get(API_URL, () => HttpResponse.json(hydraCollection([], 0))));

    const { result } = renderHookWithProviders(() =>
      useGetFullCollection(BASE_URL, FETCH_OPTIONS, { path: "/utilisateurs" }),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.items).toHaveLength(0);
    expect(result.current.totalItems).toBe(0);
    expect(result.current.fetchedCount).toBe(0);
  });

  it("collection tenant sur une seule page : data contient tous les items", async () => {
    const items = [{ uid: "u1" }, { uid: "u2" }, { uid: "u3" }];
    server.use(http.get(API_URL, () => HttpResponse.json(hydraCollection(items, 3))));

    const { result } = renderHookWithProviders(() =>
      useGetFullCollection(BASE_URL, FETCH_OPTIONS, { path: "/utilisateurs" }),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.items).toHaveLength(3);
    expect(result.current.fetchedCount).toBe(3);
    expect(result.current.totalItems).toBe(3);
  });

  it("pagination multi-pages : toutes les pages sont agrégées", async () => {
    const pages: Record<string, unknown[]> = {
      "1": [{ uid: "u1" }, { uid: "u2" }],
      "2": [{ uid: "u3" }, { uid: "u4" }],
      "3": [{ uid: "u5" }],
    };

    server.use(
      http.get(API_URL, ({ request }) => {
        const page = new URL(request.url).searchParams.get("page") ?? "1";
        return HttpResponse.json(hydraCollection(pages[page] ?? [], 5));
      }),
    );

    const { result } = renderHookWithProviders(() =>
      useGetFullCollection(BASE_URL, FETCH_OPTIONS, {
        path: "/utilisateurs",
        itemsPerPage: 2,
      }),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.items).toHaveLength(5);
    expect(result.current.fetchedCount).toBe(5);
    expect(result.current.data?.totalItems).toBe(5);
  });

  it("fenêtre glissante CONCURRENCY=5 : >5 pages toutes récupérées sans blocage", async () => {
    // 8 pages × 2 items = 16 items — dépasse la fenêtre glissante par défaut (5)
    server.use(
      http.get(API_URL, ({ request }) => {
        const page = parseInt(new URL(request.url).searchParams.get("page") ?? "1", 10);
        return HttpResponse.json(
          hydraCollection(
            [{ uid: `u${(page - 1) * 2 + 1}` }, { uid: `u${(page - 1) * 2 + 2}` }],
            16,
          ),
        );
      }),
    );

    const { result } = renderHookWithProviders(() =>
      useGetFullCollection(BASE_URL, FETCH_OPTIONS, {
        path: "/utilisateurs",
        itemsPerPage: 2,
      }),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true), { timeout: 3000 });
    expect(result.current.data?.items).toHaveLength(16);
    expect(result.current.fetchedCount).toBe(16);
  });

  it("enabled = false : aucun fetch, état initial inactif", () => {
    const { result } = renderHookWithProviders(() =>
      useGetFullCollection(BASE_URL, FETCH_OPTIONS, {
        path: "/utilisateurs",
        enabled: false,
      }),
    );

    expect(result.current.isLoading).toBe(false);
    expect(result.current.data).toBeUndefined();
    expect(result.current.isSuccess).toBe(false);
  });

  it("cas limite fetchedCount >= totalItems : allDone sans attendre les pages restantes", async () => {
    // totalItems=4, itemsPerPage=2 → totalPages=2 mais page 1 livre déjà 4 items (incohérence)
    server.use(
      http.get(API_URL, ({ request }) => {
        const page = new URL(request.url).searchParams.get("page");
        if (page === "1") {
          return HttpResponse.json(
            hydraCollection([{ uid: "u1" }, { uid: "u2" }, { uid: "u3" }, { uid: "u4" }], 4),
          );
        }
        return HttpResponse.json(hydraCollection([], 4));
      }),
    );

    const { result } = renderHookWithProviders(() =>
      useGetFullCollection(BASE_URL, FETCH_OPTIONS, {
        path: "/utilisateurs",
        itemsPerPage: 2,
      }),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.fetchedCount).toBeGreaterThanOrEqual(result.current.totalItems);
  });

  it("stableData : la référence data ne change pas entre deux re-renders sans mutation", async () => {
    server.use(http.get(API_URL, () => HttpResponse.json(hydraCollection([{ uid: "u1" }], 1))));

    const { result, rerender } = renderHookWithProviders(() =>
      useGetFullCollection(BASE_URL, FETCH_OPTIONS, { path: "/utilisateurs" }),
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    const dataRef = result.current.data;

    rerender();
    expect(result.current.data).toBe(dataRef);
  });

  it("erreur HTTP : isError = true, onError appelé, data indéfini", async () => {
    server.use(
      http.get(API_URL, () =>
        HttpResponse.json({ "hydra:description": "Forbidden" }, { status: 403 }),
      ),
    );

    const onError = vi.fn();
    const { result } = renderHookWithProviders(() =>
      useGetFullCollection(BASE_URL, FETCH_OPTIONS, {
        path: "/utilisateurs",
        onError,
      }),
    );

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.data).toBeUndefined();
    expect(onError).toHaveBeenCalledOnce();
  });
});
