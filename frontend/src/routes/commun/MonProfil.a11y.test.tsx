import React from "react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import MonProfil from "./MonProfil";

// ─── Mocks ────────────────────────────────────────────────────────────────────

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useNavigate: () => vi.fn() };
});

// lg: false → tabPlacement=undefined (tabs horizontaux) : les tabs verticaux Ant Design
// provoquent "Cannot use 'in' operator to search for 'destroyInactiveTabPane' in null" en jsdom.
vi.mock("antd/es/grid/hooks/useBreakpoint", () => ({
  default: () => ({ lg: false, md: true }),
}));

// Sous-composants du profil — isolés pour ne pas cascader leurs dépendances API
vi.mock("@controls/Profil/MonProfilContact", () => ({
  MonProfilContact: () => (
    <div>
      <label htmlFor="tel">Téléphone</label>
      <input id="tel" type="tel" />
    </div>
  ),
}));

vi.mock("@controls/Profil/MonProfilCampus", () => ({
  MonProfilCampus: () => <div data-testid="profil-campus">Campus</div>,
}));

vi.mock("@controls/Profil/MonProfilCompetences", () => ({
  MonProfilCompetences: () => <div data-testid="profil-competences">Compétences</div>,
}));

vi.mock("@controls/Profil/MonProfilNotification", () => ({
  MonProfilNotification: () => <div data-testid="profil-notification">Notifications</div>,
  notificationFrequences: [],
}));

vi.mock("@controls/Profil/MonProfilSynchro", () => ({
  MonProfilSynchro: () => <div data-testid="profil-synchro">Synchronisation</div>,
}));

vi.mock("@controls/Profil/MonProfilContactPhase", () => ({
  MonProfilContactPhase: () => <div data-testid="profil-contact-phase">Contact phase</div>,
}));

const { mockUseAuth } = vi.hoisted(() => ({
  mockUseAuth: vi.fn(),
}));

vi.mock("@/auth/AuthProvider", () => ({ useAuth: mockUseAuth }));

const { mockUseGetItem, mockUseGetFullCollection, mockUsePatch } = vi.hoisted(() => ({
  mockUseGetItem: vi.fn(() => ({ data: undefined, isFetching: false })),
  mockUseGetFullCollection: vi.fn(() => ({ data: undefined, isLoading: false })),
  mockUsePatch: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetItem: mockUseGetItem,
    useGetFullCollection: mockUseGetFullCollection,
    usePatch: mockUsePatch,
  }),
}));

// ─── Helpers ──────────────────────────────────────────────────────────────────

const INTERVENANT_USER = {
  "@id": "/utilisateurs/i@test.fr",
  uid: "i@test.fr",
  roles: ["ROLE_INTERVENANT"],
  isIntervenant: true,
  isBeneficiaire: false,
  gestionnairesActifs: [],
  isIntervenantOuRenfort: true,
};

const BENEFICIAIRE_USER = {
  "@id": "/utilisateurs/b@test.fr",
  uid: "b@test.fr",
  roles: ["ROLE_BENEFICIAIRE"],
  isIntervenant: false,
  isBeneficiaire: true,
  gestionnairesActifs: ["/utilisateurs/g@test.fr"],
  isIntervenantOuRenfort: false,
};

// ─── Y11 : MonProfil — accessibilité ─────────────────────────────────────────

describe("MonProfil — accessibilité (Y11)", () => {
  // ── Intervenant ───────────────────────────────────────────────────────────

  describe("en tant qu'intervenant", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({ user: INTERVENANT_USER });
      mockUseGetItem.mockReturnValue({ data: undefined, isFetching: false });
      mockUseGetFullCollection.mockReturnValue({ data: undefined, isLoading: false });
      mockUsePatch.mockReturnValue({ mutate: vi.fn(), isPending: false });
    });

    it("aucune violation axe-core (données non chargées)", async () => {
      const { container } = renderWithProviders(<MonProfil />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("contient un titre h1 'Mon profil'", () => {
      renderWithProviders(<MonProfil />);
      expect(screen.getByRole("heading", { level: 1, name: /mon profil/i })).toBeInTheDocument();
    });

    it("le tablist est présent avec les onglets accessibles", async () => {
      renderWithProviders(<MonProfil />);
      expect(screen.getByRole("tablist")).toBeInTheDocument();
      expect(await screen.findByRole("tab", { name: /pour vous contacter/i })).toBeInTheDocument();
      expect(screen.getByRole("tab", { name: /campus/i })).toBeInTheDocument();
      expect(screen.getByRole("tab", { name: /compétences/i })).toBeInTheDocument();
      expect(screen.getByRole("tab", { name: /préférences de notification/i })).toBeInTheDocument();
    });

    it("l'onglet actif a aria-selected='true'", async () => {
      renderWithProviders(<MonProfil />);
      const contactTab = await screen.findByRole("tab", { name: /pour vous contacter/i });
      expect(contactTab).toHaveAttribute("aria-selected", "true");
    });

    // Vérifie le pattern "roving tabindex" WCAG : seul l'onglet actif est dans le taborder
    // (tabindex="0"), les autres ont tabindex="-1" (navigation via flèches pour les AT).
    it("les onglets non sélectionnés ont tabindex='-1' (roving tabindex WCAG)", async () => {
      renderWithProviders(<MonProfil />);
      await screen.findByRole("tab", { name: /pour vous contacter/i });
      const campusTab = screen.getByRole("tab", { name: /campus/i });
      expect(campusTab).toHaveAttribute("tabindex", "-1");
    });

    it("le bouton 'Enregistrer' est nommé et de type submit", async () => {
      renderWithProviders(<MonProfil />);
      const btn = await screen.findByRole("button", { name: /enregistrer/i });
      expect(btn).toBeInTheDocument();
      expect(btn).toHaveAttribute("type", "submit");
    });
  });

  // ── Intervenant — état chargement (Skeleton) ──────────────────────────────

  describe("en tant qu'intervenant — chargement en cours", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({ user: INTERVENANT_USER });
      mockUseGetItem.mockReturnValue({ data: undefined, isFetching: true });
      mockUseGetFullCollection.mockReturnValue({ data: undefined, isLoading: false });
      mockUsePatch.mockReturnValue({ mutate: vi.fn(), isPending: false });
    });

    // Note : axe non exécuté sur le Skeleton — Ant Design génère un <h3> vide dans
    // son composant Skeleton (même bug que Y19 TypeDemandeContent) qui déclenche
    // la règle "empty-heading". Ce n'est pas un bug de l'application, mais d'AntD.
    it("le titre h1 'Mon profil' est présent pendant le chargement", () => {
      renderWithProviders(<MonProfil />);
      expect(screen.getByRole("heading", { level: 1, name: /mon profil/i })).toBeInTheDocument();
    });

    it("le Skeleton est affiché pendant le chargement", () => {
      renderWithProviders(<MonProfil />);
      // Ant Design Skeleton rend la classe ant-skeleton
      const { container } = renderWithProviders(<MonProfil />);
      expect(container.querySelector(".ant-skeleton")).toBeInTheDocument();
    });
  });

  // ── Bénéficiaire ──────────────────────────────────────────────────────────

  describe("en tant que bénéficiaire", () => {
    beforeEach(() => {
      vi.clearAllMocks();
      mockUseAuth.mockReturnValue({ user: BENEFICIAIRE_USER });
      mockUseGetItem.mockReturnValue({ data: undefined, isFetching: false });
      mockUseGetFullCollection.mockReturnValue({ data: undefined, isLoading: false });
      mockUsePatch.mockReturnValue({ mutate: vi.fn(), isPending: false });
    });

    it("aucune violation axe-core", async () => {
      const { container } = renderWithProviders(<MonProfil />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("contient un titre h1 'Mon profil'", () => {
      renderWithProviders(<MonProfil />);
      expect(screen.getByRole("heading", { level: 1, name: /mon profil/i })).toBeInTheDocument();
    });

    it("les onglets spécifiques intervenant (campus/compétences) sont absents", async () => {
      renderWithProviders(<MonProfil />);
      await screen.findByRole("tab", { name: /pour vous contacter/i });
      expect(screen.queryByRole("tab", { name: /campus/i })).not.toBeInTheDocument();
      expect(screen.queryByRole("tab", { name: /compétences/i })).not.toBeInTheDocument();
    });

    it("l'onglet 'Contact phase' est affiché pour un bénéficiaire avec gestionnaire", async () => {
      renderWithProviders(<MonProfil />);
      expect(await screen.findByRole("tab", { name: /votre contact sae/i })).toBeInTheDocument();
    });

    it("le bouton 'Enregistrer' est accessible", async () => {
      renderWithProviders(<MonProfil />);
      expect(await screen.findByRole("button", { name: /enregistrer/i })).toBeInTheDocument();
    });
  });
});
