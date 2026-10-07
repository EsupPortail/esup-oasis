import React from "react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import AlertCompleterProfil from "./AlertCompleterProfil";

// ─── Mocks ────────────────────────────────────────────────────────────────────

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useNavigate: () => vi.fn() };
});

const { mockUseAuth } = vi.hoisted(() => ({
  mockUseAuth: vi.fn(),
}));

vi.mock("@/auth/AuthProvider", () => ({ useAuth: mockUseAuth }));

// ─── Y14 (composant délégué) : AlertCompleterProfil — accessibilité ───────────

describe("AlertCompleterProfil — accessibilité", () => {
  // ── Admin ou non-intervenant → rendu vide ─────────────────────────────────

  describe("utilisateur admin", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({
        user: {
          isAdmin: true,
          isIntervenant: false,
          campus: [],
          competences: [],
        },
      });
    });

    it("aucune violation axe-core (rendu vide)", async () => {
      const { container } = renderWithProviders(<AlertCompleterProfil />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("aucune alerte n'est rendue pour un admin", () => {
      renderWithProviders(<AlertCompleterProfil />);
      expect(screen.queryByRole("alert")).not.toBeInTheDocument();
    });
  });

  // ── Intervenant avec profil incomplet → alerte visible ────────────────────

  describe("intervenant avec profil incomplet (campus et compétences vides)", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({
        user: {
          isAdmin: false,
          isIntervenant: true,
          campus: [],
          competences: [],
        },
      });
    });

    it("aucune violation axe-core", async () => {
      const { container } = renderWithProviders(<AlertCompleterProfil />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("l'alerte est rendue avec role='alert'", () => {
      renderWithProviders(<AlertCompleterProfil />);
      expect(screen.getByRole("alert")).toBeInTheDocument();
    });

    it("le texte de l'alerte est lisible par les lecteurs d'écran", () => {
      renderWithProviders(<AlertCompleterProfil />);
      expect(screen.getByText(/votre profil est incomplet/i)).toBeInTheDocument();
    });

    it("le bouton 'Compléter mon profil' est accessible", () => {
      renderWithProviders(<AlertCompleterProfil />);
      const btn = screen.getByRole("button", { name: /compléter mon profil/i });
      expect(btn).toBeInTheDocument();
    });
  });

  // ── Intervenant avec profil complet → rendu vide ──────────────────────────

  describe("intervenant avec profil complet (campus et compétences renseignés)", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({
        user: {
          isAdmin: false,
          isIntervenant: true,
          campus: ["/campus/1"],
          competences: ["/competences/1"],
        },
      });
    });

    it("aucune violation axe-core (rendu vide)", async () => {
      const { container } = renderWithProviders(<AlertCompleterProfil />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("aucune alerte n'est rendue pour un profil complet", () => {
      renderWithProviders(<AlertCompleterProfil />);
      expect(screen.queryByRole("alert")).not.toBeInTheDocument();
    });
  });
});
