import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import { IComposante } from "@api";
import { ComposanteEdition } from "./ComposanteEdition";

// --- Hoisted mocks ---
const { mockEnv } = vi.hoisted(() => ({
  mockEnv: { REACT_APP_PARAPHEUR: "" } as { REACT_APP_PARAPHEUR: string | null },
}));

vi.mock("@/env", () => ({ env: mockEnv }));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    usePatch: vi.fn(() => ({ mutate: vi.fn() })),
  }),
}));

vi.mock("@controls/Forms/UtilisateurFormItemSelect", () => ({
  default: () => null,
}));

const composante = {
  "@id": "/composantes/1",
  id: 1,
  libelle: "Composante 1",
  circuitSignature: null,
  referents: [],
} as unknown as IComposante;

describe("ComposanteEdition", () => {
  beforeEach(() => {
    mockEnv.REACT_APP_PARAPHEUR = "";
  });

  it("n'affiche pas le circuit de signature sans parapheur", () => {
    renderWithProviders(<ComposanteEdition editedItem={composante} setEditedItem={vi.fn()} />);

    expect(screen.getByText("Éditer les référent•es de composante")).toBeInTheDocument();
    expect(screen.queryByLabelText("Circuit de signature")).not.toBeInTheDocument();
  });

  it("affiche le circuit de signature quand un parapheur est configuré", () => {
    mockEnv.REACT_APP_PARAPHEUR = "factice";

    renderWithProviders(<ComposanteEdition editedItem={composante} setEditedItem={vi.fn()} />);

    expect(screen.getByText("Éditer la composante")).toBeInTheDocument();
    expect(screen.getByLabelText("Circuit de signature")).toBeInTheDocument();
  });
});
