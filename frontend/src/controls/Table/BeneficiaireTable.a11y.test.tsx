import React from "react";
import { screen } from "@testing-library/react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import BeneficiaireTable from "./BeneficiaireTable";

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetCollectionPaginated: vi.fn(() => ({ data: undefined, isFetching: false })),
    useGetFullCollection: vi.fn(() => ({ data: undefined, isLoading: false, isFetching: false })),
    useGetItem: vi.fn(() => ({ data: undefined, isLoading: false })),
  }),
}));

vi.mock("@/auth/AuthProvider", () => ({
  useAuth: () => ({
    user: { isGestionnaire: false, isAdmin: false, uid: "test@test.fr" },
    impersonate: undefined,
  }),
}));

vi.mock("@context/utilisateurPreferences/UtilisateurPreferencesProvider", () => ({
  usePreferences: () => ({
    getPreferenceArray: vi.fn(() => []),
    getPreferenceJson: vi.fn(() => ({})),
    setPreferenceJson: vi.fn(),
    preferencesChargees: true,
  }),
}));

vi.mock("@controls/Table/hooks/useFiltreSessionStorage", () => ({
  useFiltreSessionStorage: () => ({ enabled: false, toggle: vi.fn() }),
}));

vi.mock("@context/drawers/DrawersContext", () => ({
  useDrawers: () => ({ setDrawerUtilisateur: vi.fn() }),
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

describe("BeneficiaireTable — accessibilité (Y1)", () => {
  beforeEach(() => vi.clearAllMocks());

  it("aucune violation axe-core (table vide)", async () => {
    const { container } = renderWithProviders(<BeneficiaireTable />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it("les en-têtes de colonnes nommés ont un nom accessible", () => {
    renderWithProviders(<BeneficiaireTable />);
    const headers = screen.getAllByRole("columnheader");
    expect(headers.length).toBeGreaterThan(0);
    // Les colonnes nommées (hors colonne d'actions) ont un texte ou aria-label
    const namedHeaders = headers.filter(
      (h) => (h.textContent?.trim().length ?? 0) > 0 || h.getAttribute("aria-label"),
    );
    expect(namedHeaders.length).toBeGreaterThan(0);
  });

  it("le tableau a le rôle 'table'", () => {
    renderWithProviders(<BeneficiaireTable />);
    expect(screen.getByRole("table")).toBeInTheDocument();
  });
});
