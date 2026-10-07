import React from "react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import { ServicesFaits, ServicesFaitsIntervenantTable } from "./ServicesFaits";

// ─── Mocks ────────────────────────────────────────────────────────────────────

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return {
    ...actual,
    useSearchParams: () => [new URLSearchParams(), vi.fn()],
  };
});

const { mockUseGetFullCollection, mockUseGetCollectionPaginated } = vi.hoisted(() => ({
  mockUseGetFullCollection: vi.fn(
    () =>
      ({ data: undefined as unknown, isFetching: false }) as {
        data: unknown;
        isFetching: boolean;
        isError?: boolean;
      },
  ),
  mockUseGetCollectionPaginated: vi.fn(
    () =>
      ({ data: undefined as unknown, isLoading: false }) as {
        data: unknown;
        isLoading: boolean;
        isError?: boolean;
      },
  ),
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetFullCollection: mockUseGetFullCollection,
    useGetCollectionPaginated: mockUseGetCollectionPaginated,
  }),
}));

vi.mock("@/auth/AuthProvider", () => ({
  useAuth: () => ({
    user: { "@id": "/utilisateurs/i@test.fr", uid: "i@test.fr" },
  }),
}));

vi.mock("antd/es/grid/hooks/useBreakpoint", () => ({
  default: () => ({ lg: true, md: true }),
}));

vi.mock("@controls/ServicesFaits/EvenementsEnCoursTable", () => ({
  EvenementsEnCoursTable: () => (
    <table aria-label="Évènements en cours">
      <thead>
        <tr>
          <th scope="col">Évènement</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Aucun évènement</td>
        </tr>
      </tbody>
    </table>
  ),
}));

vi.mock("@controls/ServicesFaits/InterventionsForfaitEnCoursTable", () => ({
  InterventionsForfaitEnCoursTable: () => (
    <table aria-label="Interventions au forfait en cours">
      <thead>
        <tr>
          <th scope="col">Intervention</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Aucune intervention</td>
        </tr>
      </tbody>
    </table>
  ),
}));

vi.mock("@controls/Table/ServicesFaitsDetailsTable", () => ({
  ServicesFaitsDetailsTable: () => (
    <div data-testid="services-faits-details">Détail des services faits</div>
  ),
}));

vi.mock("@context/modals/ModalsContext", () => ({
  useModals: () => ({ setModalEvenementId: vi.fn() }),
}));

vi.mock("@controls/Spinner/Spinner", () => ({
  default: () => <div aria-label="Chargement en cours" role="status" />,
}));

vi.mock("@controls/Table/EvenementTableColumns", () => ({
  evenementTableColumns: () => [],
}));

vi.mock("@controls/Calendar/TimezoneAlert", () => ({
  TimezoneAlert: () => null,
}));

vi.mock("@controls/Items/PeriodeRhItem", () => ({
  PeriodeRhItem: () => <span>Période RH</span>,
}));

// ─── Y13 : ServicesFaits — accessibilité ─────────────────────────────────────

describe("ServicesFaits — accessibilité (Y13)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseGetFullCollection.mockReturnValue({ data: undefined, isFetching: false });
    mockUseGetCollectionPaginated.mockReturnValue({ data: undefined, isLoading: false });
  });

  // ── État vide (données non chargées) ──────────────────────────────────────

  describe("état vide (données non chargées)", () => {
    it("aucune violation axe-core", async () => {
      const { container } = renderWithProviders(<ServicesFaits />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("la page contient un titre h1 'Services faits'", () => {
      renderWithProviders(<ServicesFaits />);
      expect(
        screen.getByRole("heading", { level: 1, name: /services faits/i }),
      ).toBeInTheDocument();
    });

    it("le tablist est présent", () => {
      renderWithProviders(<ServicesFaits />);
      // Ant Design Tabs vertical ne propage pas aria-label au tablist — les tabs sont accessibles par leur nom
      expect(screen.getByRole("tablist")).toBeInTheDocument();
    });

    it("les onglets 'En cours' et 'Historique' sont accessibles par leur nom", async () => {
      renderWithProviders(<ServicesFaits />);
      // findByRole car Ant Design initialise les tabs de façon asynchrone
      expect(await screen.findByRole("tab", { name: /en cours/i })).toBeInTheDocument();
      expect(screen.getByRole("tab", { name: /historique/i })).toBeInTheDocument();
    });

    it("le tableau des évènements a un aria-label", () => {
      renderWithProviders(<ServicesFaits />);
      expect(screen.getByRole("table", { name: /évènements en cours/i })).toBeInTheDocument();
    });
  });

  // ── Y22 : état d'erreur réseau (données indéfinies forcées) ───────────────
  // Note : ServicesFaits n'a pas d'Alert inline en cas d'erreur API.
  // Les erreurs sont gérées globalement via notification.error (Ant Design).
  // Ces tests vérifient la dégradation gracieuse : pas de violation axe introduite.

  describe("Y22 — état d'erreur réseau (dégradation gracieuse)", () => {
    beforeEach(() => {
      // Simule l'état post-erreur : data=undefined (React Query garde undefined après échec)
      mockUseGetFullCollection.mockReturnValue({
        data: undefined,
        isFetching: false,
        isError: true,
      });
      mockUseGetCollectionPaginated.mockReturnValue({
        data: undefined,
        isLoading: false,
        isError: true,
      });
    });

    it("aucune violation axe-core en état d'erreur", async () => {
      const { container } = renderWithProviders(<ServicesFaits />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("la structure de page reste valide (h1 présent) en état d'erreur", () => {
      renderWithProviders(<ServicesFaits />);
      expect(
        screen.getByRole("heading", { level: 1, name: /services faits/i }),
      ).toBeInTheDocument();
    });

    // Les tabs sont disponibles après initialisation async d'Ant Design
    it("les onglets restent navigables en état d'erreur", async () => {
      renderWithProviders(<ServicesFaits />);
      expect(await screen.findByRole("tab", { name: /en cours/i })).toBeInTheDocument();
      expect(screen.getByRole("tab", { name: /historique/i })).toBeInTheDocument();
    });
  });
});

// ─── Y18 : ServicesFaits — onglets & modal ────────────────────────────────────

describe("ServicesFaits — onglets & modal (Y18)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseGetFullCollection.mockReturnValue({ data: undefined, isFetching: false });
    mockUseGetCollectionPaginated.mockReturnValue({ data: undefined, isLoading: false });
  });

  // ── Navigation clavier entre onglets ─────────────────────────────────────

  describe("navigation clavier entre onglets", () => {
    it("l'onglet 'En cours' est sélectionné par défaut (aria-selected='true')", async () => {
      renderWithProviders(<ServicesFaits />);
      const tabEnCours = await screen.findByRole("tab", { name: /en cours/i });
      expect(tabEnCours).toHaveAttribute("aria-selected", "true");
    });

    it("cliquer sur 'Historique' met à jour aria-selected", async () => {
      const user = userEvent.setup();
      renderWithProviders(<ServicesFaits />);

      const tabHistorique = await screen.findByRole("tab", { name: /historique/i });
      await user.click(tabHistorique);

      await waitFor(() => {
        expect(screen.getByRole("tab", { name: /historique/i })).toHaveAttribute(
          "aria-selected",
          "true",
        );
        expect(screen.getByRole("tab", { name: /en cours/i })).toHaveAttribute(
          "aria-selected",
          "false",
        );
      });
    });

    // Vérifie le pattern "roving tabindex" WCAG : l'onglet non sélectionné a tabindex="-1"
    // (navigation via flèches pour les technologies d'assistance).
    it("l'onglet 'Historique' non sélectionné a tabindex='-1' (roving tabindex WCAG)", async () => {
      renderWithProviders(<ServicesFaits />);
      await screen.findByRole("tab", { name: /en cours/i });
      expect(screen.getByRole("tab", { name: /historique/i })).toHaveAttribute("tabindex", "-1");
    });

    it("aucune violation axe-core après basculement sur l'onglet Historique", async () => {
      const user = userEvent.setup();
      const { container } = renderWithProviders(<ServicesFaits />);

      const tabHistorique = await screen.findByRole("tab", { name: /historique/i });
      await user.click(tabHistorique);

      await waitFor(() => {
        expect(screen.getByRole("tab", { name: /historique/i })).toHaveAttribute(
          "aria-selected",
          "true",
        );
      });

      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("le contenu de l'onglet 'En cours' contient les tableaux accessibles", async () => {
      renderWithProviders(<ServicesFaits />);
      // L'onglet En cours est actif par défaut
      expect(
        await screen.findByRole("table", { name: /évènements en cours/i }),
      ).toBeInTheDocument();
      expect(
        screen.getByRole("table", { name: /interventions au forfait en cours/i }),
      ).toBeInTheDocument();
    });
  });

  // ── Modal de détail (ServicesFaitsIntervenantTable) ───────────────────────

  describe("modal de détail des services faits", () => {
    const MOCK_SERVICE_FAIT = {
      "@id": "/services_faits/1",
      debut: "2025-01-01",
      fin: "2025-01-31",
      periode: {
        "@id": "/periodes/1",
        debut: "2025-01-01",
        fin: "2025-01-31",
        libelle: "Janvier 2025",
      },
      lignes: [
        {
          "@id": "/services_faits_lignes/1",
          nbHeures: "10",
          tauxHoraire: { "@id": "/taux_horaires/1", montant: "25.00" },
        },
      ],
    };

    const MOCK_PAGINATED_DATA = {
      items: [MOCK_SERVICE_FAIT],
      totalItems: 1,
      itemsPerPage: 12,
    };

    beforeEach(() => {
      vi.clearAllMocks();
      mockUseGetCollectionPaginated.mockReturnValue({
        data: MOCK_PAGINATED_DATA,
        isLoading: false,
      });
    });

    it("le bouton 'Consulter le détail' est accessible", async () => {
      renderWithProviders(<ServicesFaitsIntervenantTable />);
      const btn = await screen.findByRole("button", { name: /consulter le détail/i });
      expect(btn).toBeInTheDocument();
    });

    it("cliquer sur 'Consulter le détail' ouvre une modale avec role='dialog'", async () => {
      const user = userEvent.setup();
      renderWithProviders(<ServicesFaitsIntervenantTable />);

      const btn = await screen.findByRole("button", { name: /consulter le détail/i });
      await user.click(btn);

      expect(await screen.findByRole("dialog")).toBeInTheDocument();
    });

    it("la modale contient un titre accessible", async () => {
      const user = userEvent.setup();
      renderWithProviders(<ServicesFaitsIntervenantTable />);

      const btn = await screen.findByRole("button", { name: /consulter le détail/i });
      await user.click(btn);

      await screen.findByRole("dialog");
      expect(screen.getByText(/résumé des services faits de la période/i)).toBeInTheDocument();
    });

    it("aucune violation axe-core avec la modale ouverte", async () => {
      const user = userEvent.setup();
      const { container } = renderWithProviders(<ServicesFaitsIntervenantTable />);

      const btn = await screen.findByRole("button", { name: /consulter le détail/i });
      await user.click(btn);

      await screen.findByRole("dialog");
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });

    it("le bouton 'Fermer' ferme la modale", async () => {
      const user = userEvent.setup();
      renderWithProviders(<ServicesFaitsIntervenantTable />);

      const btn = await screen.findByRole("button", { name: /consulter le détail/i });
      await user.click(btn);
      await screen.findByRole("dialog");

      const fermerBtn = screen.getByRole("button", { name: /fermer/i });
      await user.click(fermerBtn);

      await waitFor(() => {
        expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
      });
    });
  });
});
