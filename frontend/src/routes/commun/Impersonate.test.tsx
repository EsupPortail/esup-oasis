/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { waitFor } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import Impersonate from "./Impersonate";

// --- Mocks ---
// `@/env` et `@/queryClient` sont lus dès l'import (par `@lib` / `Impersonate`) →
// leur état doit exister avant les imports (vi.hoisted).
const { mockEnv, mockQueryClientClear } = vi.hoisted(() => ({
  mockEnv: { REACT_APP_ENVIRONMENT: "test", REACT_APP_SERVICE: "SAE" } as Record<string, string>,
  mockQueryClientClear: vi.fn(),
}));
vi.mock("@/env", () => ({ env: mockEnv }));
vi.mock("@/queryClient", () => ({ queryClient: { clear: mockQueryClientClear } }));

const mockNavigate = vi.fn();
let mockParams: { uid?: string } = { uid: "cible@test.fr" };

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useNavigate: () => mockNavigate, useParams: () => mockParams };
});

const mockSetImpersonate = vi.fn();
const mockSetAffichageFiltres = vi.fn();
let mockAuth: {
  user?: { isAdmin: boolean };
  impersonate?: string;
  loading: boolean;
  setImpersonate: typeof mockSetImpersonate;
};

vi.mock("@/auth/AuthProvider", () => ({
  useAuth: () => mockAuth,
}));

vi.mock("@context/affichageFiltres/AffichageFiltresContext", () => ({
  useAffichageFiltres: () => ({ setAffichageFiltres: mockSetAffichageFiltres }),
  initialAffichageFiltres: { affichage: {}, filtres: {} },
}));

vi.mock("@controls/Spinner/Spinner", () => ({ default: () => <div data-testid="spinner" /> }));

describe("Impersonate — garde d'accès", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockParams = { uid: "cible@test.fr" };
    mockAuth = {
      user: { isAdmin: true },
      impersonate: undefined,
      loading: false,
      setImpersonate: mockSetImpersonate,
    };
    mockEnv.REACT_APP_ENVIRONMENT = "test";
  });

  it("admin hors production : déclenche l'impersonation de l'uid demandé", () => {
    renderWithProviders(<Impersonate />, { withRouter: false });
    expect(mockSetImpersonate).toHaveBeenCalledWith("cible@test.fr");
    expect(mockSetAffichageFiltres).toHaveBeenCalled();
  });

  it("utilisateur non-admin : redirige vers / sans impersonation", () => {
    mockAuth.user = { isAdmin: false };
    renderWithProviders(<Impersonate />, { withRouter: false });
    expect(mockSetImpersonate).not.toHaveBeenCalled();
    expect(mockNavigate).toHaveBeenCalledWith("/");
  });

  it("environnement de production : impersonation refusée même pour un admin", () => {
    mockEnv.REACT_APP_ENVIRONMENT = "production";
    renderWithProviders(<Impersonate />, { withRouter: false });
    expect(mockSetImpersonate).not.toHaveBeenCalled();
    expect(mockNavigate).toHaveBeenCalledWith("/");
  });

  it("uid manquant : redirige vers / sans impersonation", () => {
    mockParams = {};
    renderWithProviders(<Impersonate />, { withRouter: false });
    expect(mockSetImpersonate).not.toHaveBeenCalled();
    expect(mockNavigate).toHaveBeenCalledWith("/");
  });

  it("une fois l'impersonation établie et le chargement terminé : vide le cache et navigue", async () => {
    mockAuth = {
      user: { isAdmin: true },
      impersonate: "cible@test.fr",
      loading: false,
      setImpersonate: mockSetImpersonate,
    };
    renderWithProviders(<Impersonate />, { withRouter: false });
    await waitFor(() => expect(mockQueryClientClear).toHaveBeenCalled());
    expect(mockNavigate).toHaveBeenCalledWith("/");
  });

  it("affiche un spinner pendant la transition", () => {
    const { getByTestId } = renderWithProviders(<Impersonate />, { withRouter: false });
    expect(getByTestId("spinner")).toBeInTheDocument();
  });
});
