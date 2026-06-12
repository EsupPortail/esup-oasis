import React from "react";
import { renderHook, waitFor } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import { http, HttpResponse } from "msw";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { notification, message } from "antd";
import { server } from "@/mocks/server";
import { AuthProvider, useAuth } from "./AuthProvider";

// use-local-storage-state inspecte localStorage à l'import, avant le polyfill de setupTests.
const { mockUseLocalStorageState } = vi.hoisted(() => ({
  mockUseLocalStorageState: vi.fn(),
}));
vi.mock("use-local-storage-state", () => ({ default: mockUseLocalStorageState }));

vi.mock("@/auth/hook/useOAuth2", () => ({
  default: () => ({ loading: false, error: null, getAuth: vi.fn() }),
}));

vi.mock("@/queryClient", () => ({
  queryClient: { clear: vi.fn() },
}));

vi.mock("@/env", () => ({
  env: {
    REACT_APP_API: "http://api.test",
    REACT_APP_API_PREFIX: "",
    REACT_APP_ENVIRONMENT: "test",
  },
}));

vi.mock("@utils/logger", () => ({ logger: { error: vi.fn() } }));

const USER_URL = "http://api.test/utilisateurs/:uid";

function makeUser(overrides: Record<string, unknown> = {}) {
  return {
    "@id": "/utilisateurs/user@test.fr",
    uid: "user@test.fr",
    roles: ["ROLE_USER", "ROLE_GESTIONNAIRE"],
    ...overrides,
  };
}

function setupStorage({
  login = null,
  impersonate = null,
}: { login?: string | null; impersonate?: string | null } = {}) {
  mockUseLocalStorageState.mockImplementation((key: string) => {
    switch (key) {
      case "login":
        return [login, vi.fn(), { removeItem: vi.fn() }];
      case "impersonate":
        return [impersonate, vi.fn(), { removeItem: vi.fn() }];
      default:
        return [null, vi.fn(), { removeItem: vi.fn() }];
    }
  });
}

function makeWrapper(onSuccess = vi.fn()) {
  return function Wrapper({ children }: { children: React.ReactNode }) {
    return (
      <MemoryRouter>
        <AuthProvider onSuccess={onSuccess}>{children}</AuthProvider>
      </MemoryRouter>
    );
  };
}

describe("AuthProvider", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(notification, "error").mockImplementation(vi.fn());
    vi.spyOn(message, "error").mockResolvedValue(undefined as never);
  });

  it("chargement utilisateur réussi → user peuplé, onSuccess appelé", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => HttpResponse.json(makeUser())));

    const onSuccess = vi.fn();
    const { result } = renderHook(() => useAuth(), { wrapper: makeWrapper(onSuccess) });

    await waitFor(() => expect(result.current.user).toBeDefined(), { timeout: 2000 });
    expect(result.current.user?.uid).toBe("user@test.fr");
    await waitFor(() => expect(onSuccess).toHaveBeenCalled(), { timeout: 2000 });
  });

  it("réponse 401 (session absente ou expirée) → retour silencieux, sans erreur", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => new HttpResponse(null, { status: 401 })));

    const { result } = renderHook(() => useAuth(), { wrapper: makeWrapper() });

    await waitFor(() => expect(result.current.loading).toBe(false), { timeout: 2000 });
    expect(result.current.user).toBeUndefined();
    expect(result.current.error).toBeNull();
  });

  it("réponse non-ok (hors 401/403) → user undefined, error défini", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => new HttpResponse(null, { status: 500 })));

    const { result } = renderHook(() => useAuth(), { wrapper: makeWrapper() });

    await waitFor(() => expect(result.current.error).toBeTruthy(), { timeout: 2000 });
    expect(result.current.user).toBeUndefined();
  });

  it("utilisateur avec ROLE_USER uniquement → notification.error, pas de user", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => HttpResponse.json(makeUser({ roles: ["ROLE_USER"] }))));

    const { result } = renderHook(() => useAuth(), { wrapper: makeWrapper() });

    await waitFor(() => expect(notification.error).toHaveBeenCalled(), { timeout: 2000 });
    expect(result.current.user).toBeUndefined();
  });

  it("setImpersonate avec son propre uid → message.error, impersonate inchangé", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => HttpResponse.json(makeUser())));

    const { result } = renderHook(() => useAuth(), { wrapper: makeWrapper() });
    await waitFor(() => expect(result.current.user).toBeDefined(), { timeout: 2000 });

    result.current.setImpersonate("user@test.fr");

    expect(message.error).toHaveBeenCalled();
    expect(result.current.impersonate).toBeUndefined();
  });

  it("AbortController : pas de mise à jour après unmount", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(
      http.get(USER_URL, async () => {
        await new Promise<never>(() => {});
      }),
    );

    const onSuccess = vi.fn();
    const { unmount } = renderHook(() => useAuth(), { wrapper: makeWrapper(onSuccess) });

    unmount();
    await new Promise((r) => setTimeout(r, 100));

    expect(onSuccess).not.toHaveBeenCalled();
  });
});
