import React from "react";
import { act, waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { notification, message } from "antd";
import { server } from "@/mocks/server";
import { queryClient } from "@/queryClient";
import { renderHookWithProviders, makeUtilisateur } from "@/test";
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

vi.mock("@utils/logger", () => ({ logger: { error: vi.fn() } }));

const USER_URL = "http://api.test/utilisateurs/:uid";
const LOGOUT_URL = "http://api.test/connect/oauth/logout";

function makeUser(overrides: Record<string, unknown> = {}) {
  return makeUtilisateur({
    uid: "user@test.fr",
    roles: ["ROLE_USER", "ROLE_GESTIONNAIRE"],
    ...overrides,
  });
}

// Mocks stables (réinitialisés par vi.clearAllMocks) pour asserter le nettoyage de session.
const storageMocks = {
  setLogin: vi.fn(),
  removeLogin: vi.fn(),
  setImpersonate: vi.fn(),
  removeImpersonate: vi.fn(),
};

function setupStorage({
  login = null,
  impersonate = null,
}: { login?: string | null; impersonate?: string | null } = {}) {
  mockUseLocalStorageState.mockImplementation((key: string) => {
    switch (key) {
      case "login":
        return [login, storageMocks.setLogin, { removeItem: storageMocks.removeLogin }];
      case "impersonate":
        return [
          impersonate,
          storageMocks.setImpersonate,
          { removeItem: storageMocks.removeImpersonate },
        ];
      default:
        return [null, vi.fn(), { removeItem: vi.fn() }];
    }
  });
}

function makeOptions(onSuccess = vi.fn()) {
  return {
    withRouter: true,
    extraWrappers: [
      (children: React.ReactNode) => <AuthProvider onSuccess={onSuccess}>{children}</AuthProvider>,
    ],
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
    const { result } = renderHookWithProviders(() => useAuth(), makeOptions(onSuccess));

    await waitFor(() => expect(result.current.user).toBeDefined(), { timeout: 2000 });
    expect(result.current.user?.uid).toBe("user@test.fr");
    await waitFor(() => expect(onSuccess).toHaveBeenCalled(), { timeout: 2000 });
  });

  it("réponse 401 (session absente ou expirée) → retour silencieux, sans erreur", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => new HttpResponse(null, { status: 401 })));

    const { result } = renderHookWithProviders(() => useAuth(), makeOptions());

    await waitFor(() => expect(result.current.loading).toBe(false), { timeout: 2000 });
    expect(result.current.user).toBeUndefined();
    expect(result.current.error).toBeNull();
  });

  it("réponse non-ok (hors 401/403) → user undefined, error défini", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => new HttpResponse(null, { status: 500 })));

    const { result } = renderHookWithProviders(() => useAuth(), makeOptions());

    await waitFor(() => expect(result.current.error).toBeTruthy(), { timeout: 2000 });
    expect(result.current.user).toBeUndefined();
  });

  it("utilisateur avec ROLE_USER uniquement → notification.error, pas de user", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => HttpResponse.json(makeUser({ roles: ["ROLE_USER"] }))));

    const { result } = renderHookWithProviders(() => useAuth(), makeOptions());

    await waitFor(() => expect(notification.error).toHaveBeenCalled(), { timeout: 2000 });
    expect(result.current.user).toBeUndefined();
  });

  it("setImpersonate avec son propre uid → message.error, impersonate inchangé", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => HttpResponse.json(makeUser())));

    const { result } = renderHookWithProviders(() => useAuth(), makeOptions());
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
    const { unmount } = renderHookWithProviders(() => useAuth(), makeOptions(onSuccess));

    unmount();
    await new Promise((r) => setTimeout(r, 100));

    expect(onSuccess).not.toHaveBeenCalled();
  });

  describe("signOut — nettoyage de session", () => {
    it("appelle l'API de déconnexion, efface user, marqueurs localStorage et filtres de session, puis appelle le callback", async () => {
      sessionStorage.setItem("oasis:filter:demandes", "x");
      sessionStorage.setItem("oasis:autre", "garder");
      setupStorage({ login: "user@test.fr" });
      let logoutCalled = false;
      server.use(
        http.get(USER_URL, () => HttpResponse.json(makeUser())),
        http.post(LOGOUT_URL, () => {
          logoutCalled = true;
          return HttpResponse.json({ status: "ok" });
        }),
      );

      const { result } = renderHookWithProviders(() => useAuth(), makeOptions());
      await waitFor(() => expect(result.current.user).toBeDefined(), { timeout: 2000 });

      const callback = vi.fn();
      act(() => result.current.signOut(callback));

      await waitFor(() => expect(result.current.user).toBeUndefined());
      expect(storageMocks.removeLogin).toHaveBeenCalled();
      expect(storageMocks.removeImpersonate).toHaveBeenCalled();
      expect(logoutCalled).toBe(true);
      // Seules les clés de filtres sont purgées, pas les autres entrées de session.
      expect(sessionStorage.getItem("oasis:filter:demandes")).toBeNull();
      expect(sessionStorage.getItem("oasis:autre")).toBe("garder");
      await waitFor(() => expect(callback).toHaveBeenCalled(), { timeout: 1000 });

      sessionStorage.clear();
    });
  });

  describe("impersonation", () => {
    it("charge l'utilisateur impersonné (et non le login) et persiste l'impersonate", async () => {
      setupStorage({ login: "admin@test.fr", impersonate: "cible@test.fr" });
      let requestedUid: string | undefined;
      server.use(
        http.get(USER_URL, ({ params }) => {
          requestedUid = params.uid as string;
          return HttpResponse.json(makeUser({ uid: "cible@test.fr" }));
        }),
      );

      const { result } = renderHookWithProviders(() => useAuth(), makeOptions());

      await waitFor(() => expect(result.current.user).toBeDefined(), { timeout: 2000 });
      expect(requestedUid).toBe("cible@test.fr");
      expect(result.current.impersonate).toBe("cible@test.fr");
      expect(storageMocks.setImpersonate).toHaveBeenCalledWith("cible@test.fr");
    });

    it("removeImpersonate : réinitialise l'impersonate et vide le cache", async () => {
      setupStorage({ login: "admin@test.fr", impersonate: "cible@test.fr" });
      server.use(http.get(USER_URL, () => HttpResponse.json(makeUser({ uid: "cible@test.fr" }))));

      const { result } = renderHookWithProviders(() => useAuth(), makeOptions());
      await waitFor(() => expect(result.current.impersonate).toBe("cible@test.fr"), {
        timeout: 2000,
      });

      act(() => result.current.removeImpersonate());

      await waitFor(() => expect(result.current.impersonate).toBeUndefined());
      expect(queryClient.clear).toHaveBeenCalled();
    });
  });

  it("réponse 403 (accès refusé) → retour silencieux, sans erreur ni user", async () => {
    setupStorage({ login: "user@test.fr" });
    server.use(http.get(USER_URL, () => new HttpResponse(null, { status: 403 })));

    const { result } = renderHookWithProviders(() => useAuth(), makeOptions());

    await waitFor(() => expect(result.current.loading).toBe(false), { timeout: 2000 });
    expect(result.current.user).toBeUndefined();
    expect(result.current.error).toBeNull();
  });
});
