import React from "react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import { IntervenantDashboard } from "./IntervenantDashboard";

// ─── Mocks ────────────────────────────────────────────────────────────────────

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useNavigate: () => vi.fn() };
});

// Sous-composants isolés — leurs propres dépendances API ne doivent pas cascader
vi.mock("@controls/Dashboard/AlertCompleterProfil", () => ({
  default: () => <div data-testid="alert-completer-profil" />,
}));

vi.mock("@controls/Dashboard/DashboardUtilisateurStats", () => ({
  default: ({ utilisateurId }: { utilisateurId: string }) => (
    <section aria-label="Statistiques" data-testid="stats" data-uid={utilisateurId} />
  ),
}));

vi.mock("@controls/Dashboard/IntervenantDashboardServicesFaits", () => ({
  IntervenantDashboardServicesFaits: () => (
    <section aria-label="Relevés de services faits" data-testid="services-faits">
      <h2>Relevés de services faits</h2>
    </section>
  ),
}));

// Supprime l'import CSS Dashboard.scss (module résolu par Vite mais pas jsdom)
vi.mock("@routes/intervenant/dashboard/Dashboard.scss", () => ({}));

const { mockUseAuth } = vi.hoisted(() => ({
  mockUseAuth: vi.fn(),
}));

vi.mock("@/auth/AuthProvider", () => ({ useAuth: mockUseAuth }));

// ─── Helpers ──────────────────────────────────────────────────────────────────

const INTERVENANT_USER = {
  "@id": "/utilisateurs/i@test.fr",
  uid: "i@test.fr",
  isIntervenant: true,
  isIntervenantOuRenfort: true,
};

// ─── Y12 : IntervenantDashboard — accessibilité ───────────────────────────────

describe("IntervenantDashboard — accessibilité (Y12)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseAuth.mockReturnValue({ user: INTERVENANT_USER });
  });

  it("aucune violation axe-core", async () => {
    const { container } = renderWithProviders(<IntervenantDashboard />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it("contient un titre h1 'Tableau de bord'", () => {
    renderWithProviders(<IntervenantDashboard />);
    expect(screen.getByRole("heading", { level: 1, name: /tableau de bord/i })).toBeInTheDocument();
  });

  it("le bloc statistiques est rendu pour un intervenant", () => {
    renderWithProviders(<IntervenantDashboard />);
    expect(screen.getByTestId("stats")).toBeInTheDocument();
  });

  it("le bloc services faits est rendu avec un titre de région accessible", () => {
    renderWithProviders(<IntervenantDashboard />);
    expect(screen.getByRole("region", { name: /relevés de services faits/i })).toBeInTheDocument();
  });

  it("le composant AlertCompleterProfil est monté", () => {
    renderWithProviders(<IntervenantDashboard />);
    expect(screen.getByTestId("alert-completer-profil")).toBeInTheDocument();
  });

  // ── Utilisateur sans rôle intervenant → stats masquées ───────────────────

  describe("utilisateur non intervenant (isIntervenantOuRenfort: false)", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({
        user: { ...INTERVENANT_USER, isIntervenantOuRenfort: false },
      });
    });

    it("aucune violation axe-core", async () => {
      const { container } = renderWithProviders(<IntervenantDashboard />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("le bloc statistiques n'est pas rendu", () => {
      renderWithProviders(<IntervenantDashboard />);
      expect(screen.queryByTestId("stats")).not.toBeInTheDocument();
    });
  });
});
