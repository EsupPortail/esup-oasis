import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { renderWithProviders } from "@/test";
import DemandeTable from "./DemandeTable";
import { ETAT_DEMANDE_EN_COURS, ETAT_DEMANDE_RECEPTIONNEE, ETAT_DEMANDE_CONFORME } from "@lib";

// --- Hoisted mocks ---
const { mockUseGetCollectionPaginated, mockUseGetFullCollection } = vi.hoisted(() => ({
  mockUseGetCollectionPaginated: vi.fn(() => ({ data: undefined, isFetching: false })),
  mockUseGetFullCollection: vi.fn(() => ({
    data: {
      items: [
        { "@id": ETAT_DEMANDE_EN_COURS, libelle: "En cours", color: undefined },
        { "@id": ETAT_DEMANDE_RECEPTIONNEE, libelle: "Réceptionnée", color: "purple" },
        { "@id": ETAT_DEMANDE_CONFORME, libelle: "Conforme", color: "blue" },
      ],
      totalItems: 3,
    },
    isLoading: false,
    isFetching: false,
  })),
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetCollectionPaginated: mockUseGetCollectionPaginated,
    useGetFullCollection: mockUseGetFullCollection,
    useGetItem: vi.fn(() => ({ data: undefined, isLoading: false })),
  }),
}));

vi.mock("@/auth/AuthProvider", () => ({
  useAuth: () => ({ user: undefined, impersonate: undefined }),
}));

vi.mock("@context/utilisateurPreferences/UtilisateurPreferencesProvider", () => ({
  usePreferences: () => ({
    getPreferenceArray: vi.fn(() => []),
    preferencesChargees: true,
  }),
}));

vi.mock("@controls/Table/hooks/useFiltreSessionStorage", () => ({
  useFiltreSessionStorage: () => ({ enabled: false, toggle: vi.fn() }),
}));

vi.mock("@controls/Table/DemandeTableFilters", () => ({
  DemandeTableFilters: () => null,
}));

vi.mock("@controls/Table/DemandeTableExport", () => ({
  default: () => null,
}));

vi.mock("@controls/Table/FiltreDescription", () => ({
  default: () => null,
}));

vi.mock("@controls/Table/FiltreSessionSwitch", () => ({
  FiltreSessionSwitch: () => null,
}));

vi.mock("@controls/Items/InscriptionsGroupeesItem", () => ({
  InscriptionsGroupeesItem: ({ inscriptions }: { inscriptions?: { "@id": string }[] }) => (
    <span data-testid="inscriptions">{(inscriptions ?? []).map((i) => i["@id"]).join(",")}</span>
  ),
}));

vi.mock("@utils/logger", () => ({ logger: { error: vi.fn() } }));

const testRefs = {
  table: { current: null },
  filtres: { current: null },
  filtresDetails: { current: null },
  favoris: { current: null },
};

describe("DemandeTable", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseGetCollectionPaginated.mockReturnValue({ data: undefined, isFetching: false });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("rendu sans données : le tableau s'affiche sans erreur", () => {
    renderWithProviders(<DemandeTable refs={testRefs as never} />);
    expect(screen.getByRole("table", { name: /liste des demandes/i })).toBeInTheDocument();
  });

  it("rendu sans données : aucune ligne de données n'est affichée", () => {
    renderWithProviders(<DemandeTable refs={testRefs as never} />);
    // 1 header + 1 placeholder row (Ant Design renders an empty <tr> when dataSource is empty)
    const rows = screen.getAllByRole("row");
    expect(rows).toHaveLength(2);
  });

  it("rendu avec 3 demandes : 3 lignes de données sont affichées", () => {
    mockUseGetCollectionPaginated.mockReturnValue({
      data: {
        items: [
          { "@id": "/demandes/1", id: 1, etat: ETAT_DEMANDE_EN_COURS },
          { "@id": "/demandes/2", id: 2, etat: ETAT_DEMANDE_RECEPTIONNEE },
          { "@id": "/demandes/3", id: 3, etat: ETAT_DEMANDE_CONFORME },
        ],
        totalItems: 3,
      } as never,
      isFetching: false,
    });
    renderWithProviders(<DemandeTable refs={testRefs as never} />);
    // 1 header row + 3 data rows
    expect(screen.getAllByRole("row")).toHaveLength(4);
  });

  it("colonne état : affiche le libellé de l'état pour chaque demande", () => {
    mockUseGetCollectionPaginated.mockReturnValue({
      data: {
        items: [
          { "@id": "/demandes/1", id: 1, etat: ETAT_DEMANDE_EN_COURS },
          { "@id": "/demandes/2", id: 2, etat: ETAT_DEMANDE_RECEPTIONNEE },
          { "@id": "/demandes/3", id: 3, etat: ETAT_DEMANDE_CONFORME },
        ],
        totalItems: 3,
      } as never,
      isFetching: false,
    });
    renderWithProviders(<DemandeTable refs={testRefs as never} />);
    expect(screen.getByText("En cours")).toBeInTheDocument();
    expect(screen.getByText("Réceptionnée")).toBeInTheDocument();
    expect(screen.getByText("Conforme")).toBeInTheDocument();
  });

  it("colonne inscription : transmet les inscriptions du demandeur à InscriptionsGroupeesItem", () => {
    // Les colonnes `responsive` d'Ant Design sont masquées tant qu'aucun breakpoint ne matche :
    // on simule un écran de 1024px (breakpoints jusqu'à `lg`, sans `xl`)
    const largeurEcran = 1024;
    vi.stubGlobal("matchMedia", (query: string) => ({
      matches: Number(/min-width:\s*(\d+)px/.exec(query)?.[1] ?? 0) <= largeurEcran,
      media: query,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
    }));
    // L'avatar du demandeur (affiché à partir de `lg`) utilise `useInView`
    vi.stubGlobal(
      "IntersectionObserver",
      class {
        observe = vi.fn();
        unobserve = vi.fn();
        disconnect = vi.fn();
      },
    );
    mockUseGetCollectionPaginated.mockReturnValue({
      data: {
        items: [
          {
            "@id": "/demandes/1",
            id: 1,
            etat: ETAT_DEMANDE_EN_COURS,
            demandeur: { inscriptions: [{ "@id": "/i/1" }, { "@id": "/i/2" }] },
          },
          { "@id": "/demandes/2", id: 2, etat: ETAT_DEMANDE_RECEPTIONNEE },
        ],
        totalItems: 2,
      } as never,
      isFetching: false,
    });
    renderWithProviders(<DemandeTable refs={testRefs as never} />);
    expect(screen.getByRole("columnheader", { name: /inscription/i })).toBeInTheDocument();
    expect(screen.getAllByTestId("inscriptions").map((c) => c.textContent)).toEqual([
      "/i/1,/i/2",
      "",
    ]);
  });
});
