import React from "react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import BeneficiaireDashboard from "./BeneficiaireDashboard";

// ─── Mocks ────────────────────────────────────────────────────────────────────

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useNavigate: () => vi.fn() };
});

const { mockUseAuth } = vi.hoisted(() => ({
  mockUseAuth: vi.fn(),
}));

vi.mock("@/auth/AuthProvider", () => ({ useAuth: mockUseAuth }));

vi.mock("@controls/Calendar/PlanningWithSider", () => ({
  default: () => (
    <section aria-label="Planning">
      <h2>Planning</h2>
      <p>Aucun événement à afficher.</p>
    </section>
  ),
}));

// ─── Y14 : BeneficiaireDashboard — accessibilité ──────────────────────────────

describe("BeneficiaireDashboard — accessibilité (Y14)", () => {
  // ── Bénéficiaire sans profil incomplet (AlertCompleterProfil renvoie vide) ──

  describe("utilisateur bénéficiaire (pas intervenant)", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({
        user: {
          "@id": "/utilisateurs/b@test.fr",
          uid: "b@test.fr",
          isAdmin: false,
          isIntervenant: false,
          isBeneficiaire: true,
          campus: [],
          competences: [],
        },
      });
    });

    it("aucune violation axe-core", async () => {
      const { container } = renderWithProviders(<BeneficiaireDashboard />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("le planning est rendu avec un label accessible", () => {
      renderWithProviders(<BeneficiaireDashboard />);
      expect(screen.getByRole("region", { name: /planning/i })).toBeInTheDocument();
    });

    it("aucune alerte de profil incomplet pour un non-intervenant", () => {
      renderWithProviders(<BeneficiaireDashboard />);
      expect(screen.queryByText(/profil est incomplet/i)).not.toBeInTheDocument();
    });
  });

  // ── Intervenant avec profil incomplet (AlertCompleterProfil visible) ─────────

  describe("utilisateur intervenant avec profil incomplet", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({
        user: {
          "@id": "/utilisateurs/i@test.fr",
          uid: "i@test.fr",
          isAdmin: false,
          isIntervenant: true,
          isBeneficiaire: false,
          campus: [],
          competences: [],
        },
      });
    });

    it("aucune violation axe-core", async () => {
      const { container } = renderWithProviders(<BeneficiaireDashboard />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("l'alerte de profil incomplet est rendue avec role='alert'", () => {
      renderWithProviders(<BeneficiaireDashboard />);
      expect(screen.getByRole("alert")).toBeInTheDocument();
    });

    it("l'alerte contient un bouton 'Compléter mon profil' accessible", () => {
      renderWithProviders(<BeneficiaireDashboard />);
      expect(screen.getByRole("button", { name: /compléter mon profil/i })).toBeInTheDocument();
    });
  });

  // ── Intervenant avec profil complet (AlertCompleterProfil silencieuse) ──────

  describe("utilisateur intervenant avec profil complet", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({
        user: {
          "@id": "/utilisateurs/i@test.fr",
          uid: "i@test.fr",
          isAdmin: false,
          isIntervenant: true,
          isBeneficiaire: false,
          campus: ["/campus/1"],
          competences: ["/competences/1"],
        },
      });
    });

    it("aucune violation axe-core", async () => {
      const { container } = renderWithProviders(<BeneficiaireDashboard />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("aucune alerte de profil incomplet", () => {
      renderWithProviders(<BeneficiaireDashboard />);
      expect(screen.queryByText(/profil est incomplet/i)).not.toBeInTheDocument();
    });
  });
});
