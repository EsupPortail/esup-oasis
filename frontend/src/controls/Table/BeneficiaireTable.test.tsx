import { fireEvent, screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import BeneficiaireTable from "./BeneficiaireTable";
import {
  PREF_KEY_COLONNES_BENEFICIAIRES,
  STORAGE_KEY_COLONNES_BENEFICIAIRES,
} from "./hooks/useBeneficiaireTableColumnPrefs";
import { BENEFICIAIRE_TABLE_COLUMNS_KEYS } from "./BeneficiaireTableColumns";

// --- Hoisted mocks ---
const {
  mockUseGetCollectionPaginated,
  mockGetPreferenceArray,
  mockGetPreferenceJson,
  mockSetPreferenceJson,
  mockUseGetItem,
  mockAuthUser,
} = vi.hoisted(() => ({
  mockUseGetCollectionPaginated: vi.fn(() => ({
    data: undefined as { items: unknown[]; totalItems: number } | undefined,
    isFetching: false as boolean,
  })),
  mockGetPreferenceArray: vi.fn(() => []),
  mockGetPreferenceJson: vi.fn(() => ({})),
  mockSetPreferenceJson: vi.fn(),
  mockUseGetItem: vi.fn(() => ({ data: undefined as unknown, isLoading: false })),
  mockAuthUser: {
    isGestionnaire: false,
    isAdmin: false,
    uid: "test@test.fr",
  },
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetCollectionPaginated: mockUseGetCollectionPaginated,
    useGetFullCollection: vi.fn(() => ({ data: undefined, isLoading: false, isFetching: false })),
    useGetItem: mockUseGetItem,
  }),
}));

vi.mock("@/auth/AuthProvider", () => ({
  useAuth: () => ({
    user: mockAuthUser,
    impersonate: undefined,
  }),
}));

vi.mock("@context/utilisateurPreferences/UtilisateurPreferencesProvider", () => ({
  usePreferences: () => ({
    getPreferenceArray: mockGetPreferenceArray,
    getPreferenceJson: mockGetPreferenceJson,
    setPreferenceJson: mockSetPreferenceJson,
    preferencesChargees: true,
  }),
}));

vi.mock("@controls/Table/hooks/useFiltreSessionStorage", () => ({
  useFiltreSessionStorage: () => ({ enabled: false, toggle: vi.fn() }),
}));

vi.mock("@context/drawers/DrawersContext", () => ({
  useDrawers: () => ({
    setDrawerUtilisateur: vi.fn(),
  }),
}));

vi.mock("@controls/Table/BeneficiaireTableFilter", () => ({
  BeneficiaireTableFilter: () => null,
}));

vi.mock("@controls/Table/BeneficiaireTableExport", () => ({
  default: () => null,
}));

vi.mock("@controls/Table/FiltreDescription", () => ({
  default: () => null,
}));

vi.mock("@controls/Table/FiltreSessionSwitch", () => ({
  FiltreSessionSwitch: () => null,
}));

vi.mock("@utils/logger", () => ({ logger: { error: vi.fn() } }));

const makeBeneficiaireRow = (n: number) => ({
  "@id": `/beneficiaires/user${n}@test.fr`,
  uid: `user${n}@test.fr`,
  nom: `Nom${n}`,
  prenom: `Prenom${n}`,
  email: `user${n}@test.fr`,
  roles: [],
});

describe("BeneficiaireTable", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
    mockUseGetCollectionPaginated.mockReturnValue({ data: undefined, isFetching: false });
    mockGetPreferenceArray.mockReturnValue([]);
    mockGetPreferenceJson.mockReturnValue({});
    mockAuthUser.isGestionnaire = false;
    mockAuthUser.isAdmin = false;
    mockUseGetItem.mockReturnValue({ data: undefined, isLoading: false });
  });

  it("rendu sans données : le tableau s'affiche sans erreur", () => {
    renderWithProviders(<BeneficiaireTable />);
    expect(screen.getByRole("table")).toBeInTheDocument();
  });

  it("rendu sans données : aucune ligne de données n'est affichée", () => {
    renderWithProviders(<BeneficiaireTable />);
    // 1 header row + 1 empty placeholder row (Ant Design)
    expect(screen.getAllByRole("row")).toHaveLength(2);
  });

  it("rendu avec 3 bénéficiaires : 3 lignes de données sont affichées", () => {
    mockUseGetCollectionPaginated.mockReturnValue({
      data: {
        items: [makeBeneficiaireRow(1), makeBeneficiaireRow(2), makeBeneficiaireRow(3)],
        totalItems: 3,
      },
      isFetching: false,
    });
    renderWithProviders(<BeneficiaireTable />);
    // 1 header row + 3 data rows
    expect(screen.getAllByRole("row")).toHaveLength(4);
  });

  it("affiche le libellé du compte quand des données sont présentes", () => {
    mockUseGetCollectionPaginated.mockReturnValue({
      data: {
        items: [makeBeneficiaireRow(1), makeBeneficiaireRow(2)],
        totalItems: 2,
      },
      isFetching: false,
    });
    renderWithProviders(<BeneficiaireTable />);
    expect(screen.getByText(/2 bénéficiaire/i)).toBeInTheDocument();
  });

  it("affiche le libellé du compte au singulier pour 1 bénéficiaire", () => {
    mockUseGetCollectionPaginated.mockReturnValue({
      data: {
        items: [makeBeneficiaireRow(1)],
        totalItems: 1,
      },
      isFetching: false,
    });
    renderWithProviders(<BeneficiaireTable />);
    expect(screen.getByText(/1 bénéficiaire/i)).toBeInTheDocument();
  });

  it("n'affiche pas le bouton 'Retirer les filtres' avec le filtre par défaut", () => {
    renderWithProviders(<BeneficiaireTable />);
    expect(screen.queryByText(/retirer les filtres/i)).not.toBeInTheDocument();
  });

  it("état chargement : le tableau reste présent pendant le fetch", () => {
    mockUseGetCollectionPaginated.mockReturnValue({ data: undefined, isFetching: true });
    renderWithProviders(<BeneficiaireTable />);
    expect(screen.getByRole("table")).toBeInTheDocument();
  });

  it("affiche le bouton 'Colonnes'", () => {
    renderWithProviders(<BeneficiaireTable />);
    expect(screen.getByRole("button", { name: /colonnes/i })).toBeInTheDocument();
  });

  it("ouvre la dropdown et permet de masquer une colonne", () => {
    renderWithProviders(<BeneficiaireTable />);

    // Par défaut, la colonne "Inscription" est présente dans le tableau
    expect(screen.getByRole("columnheader", { name: "Inscription" })).toBeInTheDocument();

    // Cliquer sur le bouton Colonnes pour ouvrir la dropdown
    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    // La checkbox "Inscription" est présente et cochée
    const checkboxInscription = screen.getByRole("checkbox", { name: "Inscription" });
    expect(checkboxInscription).toBeChecked();

    // Décocher "Inscription"
    fireEvent.click(checkboxInscription);

    // La colonne "Inscription" ne doit plus être dans le tableau
    expect(screen.queryByRole("columnheader", { name: "Inscription" })).not.toBeInTheDocument();

    // Refermer la dropdown
    fireEvent.click(btnColonnes);
  });

  it("permet de reinitialiser les colonnes masquees", () => {
    renderWithProviders(<BeneficiaireTable />);

    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    const checkboxInscription = screen.getByRole("checkbox", { name: "Inscription" });
    fireEvent.click(checkboxInscription);
    expect(screen.queryByRole("columnheader", { name: "Inscription" })).not.toBeInTheDocument();

    // Cliquer sur Tout afficher
    const btnReset = screen.getByRole("button", { name: "Tout afficher" });
    fireEvent.click(btnReset);

    // "Inscription" réapparaît
    expect(screen.getByRole("columnheader", { name: "Inscription" })).toBeInTheDocument();

    // Refermer la dropdown
    fireEvent.click(btnColonnes);
  });

  it("le bouton 'Réinitialiser' rétablit les colonnes et l'ordre d'origine", () => {
    renderWithProviders(<BeneficiaireTable />);

    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    // Activer une colonne qui n'était pas présente initialement (Email)
    const checkboxEmail = screen.getByRole("checkbox", { name: "Email" });
    expect(checkboxEmail).not.toBeChecked();
    fireEvent.click(checkboxEmail);
    expect(checkboxEmail).toBeChecked();

    // Décocher Inscription
    const checkboxInscription = screen.getByRole("checkbox", { name: "Inscription" });
    fireEvent.click(checkboxInscription);
    expect(checkboxInscription).not.toBeChecked();

    // Cliquer sur Réinitialiser
    const btnReset = screen.getByRole("button", { name: "Réinitialiser" });
    fireEvent.click(btnReset);

    // Inscription est de nouveau cochée, Email est décochée
    expect(screen.getByRole("checkbox", { name: "Inscription" })).toBeChecked();
    expect(screen.getByRole("checkbox", { name: "Email" })).not.toBeChecked();

    // Refermer la dropdown
    fireEvent.click(btnColonnes);
  });

  it("la colonne Bénéficiaire est toujours affichée, disabled et sans grip", () => {
    renderWithProviders(<BeneficiaireTable />);

    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    const checkboxBeneficiaire = screen.getByRole("checkbox", { name: "Bénéficiaire" });
    expect(checkboxBeneficiaire).toBeChecked();
    expect(checkboxBeneficiaire).toBeDisabled();

    // L'item Bénéficiaire n'a pas d'icône grip et n'est pas draggable
    const beneficiaireItem = screen.getByTestId("column-item-nom");
    expect(beneficiaireItem).not.toHaveAttribute("draggable", "true");
    expect(screen.queryByLabelText("Déplacer la colonne Bénéficiaire")).not.toBeInTheDocument();

    fireEvent.click(btnColonnes);
  });

  it("la colonne Actions n'est pas déplaçable", () => {
    renderWithProviders(<BeneficiaireTable />);

    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    const actionsItem = screen.getByTestId("column-item-actions");
    expect(actionsItem).not.toHaveAttribute("draggable", "true");
    expect(screen.queryByLabelText("Déplacer la colonne Actions")).not.toBeInTheDocument();

    // La colonne Actions peut être cochée/décochée
    const checkboxActions = screen.getByRole("checkbox", { name: "Actions" });
    expect(checkboxActions).not.toBeDisabled();

    fireEvent.click(btnColonnes);
  });

  it("les colonnes déplaçables affichent un grip en bout de ligne", () => {
    renderWithProviders(<BeneficiaireTable />);

    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    const inscriptionItem = screen.getByTestId("column-item-inscription");
    expect(inscriptionItem).toHaveAttribute("draggable", "true");
    expect(screen.getByLabelText("Déplacer la colonne Inscription")).toBeInTheDocument();

    fireEvent.click(btnColonnes);
  });

  it("permet de réorganiser l'ordre des colonnes par drag and drop", () => {
    renderWithProviders(<BeneficiaireTable />);

    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    const itemTags = screen.getByTestId("column-item-tags");
    const itemInscription = screen.getByTestId("column-item-inscription");

    // Glisser tags sur inscription (pour placer tags avant inscription)
    fireEvent.dragStart(itemTags);
    fireEvent.dragOver(itemInscription);
    fireEvent.drop(itemInscription);
    fireEvent.dragEnd(itemTags);

    // Vérifier dans le localStorage que le nouvel ordre a bien été persisté
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY_COLONNES_BENEFICIAIRES) || "{}");
    expect(stored.ordre).toBeDefined();
    const tagsIndex = stored.ordre.indexOf("tags");
    const inscriptionIndex = stored.ordre.indexOf("inscription");
    expect(tagsIndex).toBeLessThan(inscriptionIndex);
    expect(stored.ordre[0]).toBe("nom"); // nom toujours premier
    expect(stored.ordre[stored.ordre.length - 1]).toBe("actions"); // actions toujours dernier

    fireEvent.click(btnColonnes);
  });

  it("charge les préférences de colonnes depuis usePreferences en base", () => {
    mockGetPreferenceJson.mockReturnValue({
      ordre: ["nom", "email", "actions"],
      visibles: ["nom", "email"],
    });

    renderWithProviders(<BeneficiaireTable />);

    expect(screen.getByRole("columnheader", { name: "Email" })).toBeInTheDocument();
    expect(screen.queryByRole("columnheader", { name: "Inscription" })).not.toBeInTheDocument();
  });

  it("persiste les colonnes modifiées dans usePreferences via setPreferenceJson", () => {
    renderWithProviders(<BeneficiaireTable />);

    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    const checkboxEmail = screen.getByRole("checkbox", { name: "Email" });
    fireEvent.click(checkboxEmail);

    expect(mockSetPreferenceJson).toHaveBeenCalledWith(
      PREF_KEY_COLONNES_BENEFICIAIRES,
      expect.objectContaining({
        visibles: expect.arrayContaining(["nom", "email"]),
      }),
    );
  });

  it("persiste la réinitialisation dans usePreferences via setPreferenceJson", () => {
    renderWithProviders(<BeneficiaireTable />);

    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    const btnReset = screen.getByRole("button", { name: "Réinitialiser" });
    fireEvent.click(btnReset);

    expect(mockSetPreferenceJson).toHaveBeenCalledWith(
      PREF_KEY_COLONNES_BENEFICIAIRES,
      expect.objectContaining({
        visibles: expect.arrayContaining([
          BENEFICIAIRE_TABLE_COLUMNS_KEYS.NOM,
          BENEFICIAIRE_TABLE_COLUMNS_KEYS.INSCRIPTION,
          BENEFICIAIRE_TABLE_COLUMNS_KEYS.TAGS,
          BENEFICIAIRE_TABLE_COLUMNS_KEYS.DECISION_ETAB,
        ]),
      }),
    );
  });

  it("affiche les colonnes complémentaires dans la table quand elles sont sélectionnées", () => {
    mockAuthUser.isGestionnaire = true;
    mockUseGetItem.mockReturnValue({
      data: {
        valeursCourantes: [{ valeur: "Régime spécial" }],
      },
      isLoading: false,
    });
    mockUseGetCollectionPaginated.mockReturnValue({
      data: {
        items: [
          {
            "@id": "/utilisateurs/1",
            nom: "Dupont",
            prenom: "Jean",
            infosComplementaires: [{ libelle: "Régime spécial", valeur: "Oui" }],
          },
        ],
        totalItems: 1,
      },
      isFetching: false,
    });

    renderWithProviders(<BeneficiaireTable />);

    // Ouvrir le menu des colonnes
    const btnColonnes = screen.getByRole("button", { name: /colonnes/i });
    fireEvent.click(btnColonnes);

    // Cocher la colonne complémentaire
    const checkbox = screen.getByRole("checkbox", { name: "Régime spécial" });
    fireEvent.click(checkbox);

    // Vérifier que l'en-tête de la colonne est affiché
    expect(screen.getByRole("columnheader", { name: "Régime spécial" })).toBeInTheDocument();
    // Vérifier que la cellule affiche la valeur
    expect(screen.getByText("Oui")).toBeInTheDocument();
  });
});
