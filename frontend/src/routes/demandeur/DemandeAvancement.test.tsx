import React from "react";
import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import DemandeAvancement from "./DemandeAvancement";

let mockParams: { id?: string } = { id: "42" };

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useNavigate: () => vi.fn(), useParams: () => mockParams };
});

vi.mock("@controls/Spinner/Spinner", () => ({
  default: () => <div data-testid="spinner" />,
}));

vi.mock("@context/demande/QuestionnaireProvider", () => ({
  QuestionnaireProvider: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="questionnaire-provider">{children}</div>
  ),
  useQuestionnaire: () => ({ demande: undefined }),
}));

vi.mock("@controls/Demande/Avancement/AvancementDemande", () => ({
  default: () => <div data-testid="avancement-demande" />,
}));

vi.mock("@controls/Demande/ValidationCharte", () => ({
  ValidationCharte: () => null,
}));

describe("DemandeAvancement (route demandeur)", () => {
  beforeEach(() => vi.clearAllMocks());

  it("sans id → affiche le spinner", () => {
    mockParams = { id: undefined };
    renderWithProviders(<DemandeAvancement />);
    expect(screen.getByTestId("spinner")).toBeInTheDocument();
  });

  it("avec id → affiche le titre 'Demande'", () => {
    mockParams = { id: "42" };
    renderWithProviders(<DemandeAvancement />);
    expect(screen.getByRole("heading", { name: /demande/i, level: 1 })).toBeInTheDocument();
  });

  it("avec id → QuestionnaireProvider est monté", () => {
    mockParams = { id: "42" };
    renderWithProviders(<DemandeAvancement />);
    expect(screen.getByTestId("questionnaire-provider")).toBeInTheDocument();
  });

  it("avec id → AvancementDemande est rendu", () => {
    mockParams = { id: "42" };
    renderWithProviders(<DemandeAvancement />);
    expect(screen.getByTestId("avancement-demande")).toBeInTheDocument();
  });

  it("avec id → bouton 'Retour à la liste des demandes' est présent", () => {
    mockParams = { id: "42" };
    renderWithProviders(<DemandeAvancement />);
    expect(
      screen.getByRole("button", { name: /retour à la liste des demandes/i }),
    ).toBeInTheDocument();
  });

  it("avec id → QuestionnaireProvider reçoit l'IRI dérivé de l'id", () => {
    mockParams = { id: "99" };
    renderWithProviders(<DemandeAvancement />);
    expect(screen.getByTestId("questionnaire-provider")).toBeInTheDocument();
  });
});
