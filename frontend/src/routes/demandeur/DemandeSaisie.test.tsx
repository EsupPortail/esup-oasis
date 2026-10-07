import React from "react";
import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import DemandeSaisie from "./DemandeSaisie";

let mockParams: { id?: string } = { id: "123" };

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useParams: () => mockParams };
});

vi.mock("@controls/Spinner/Spinner", () => ({
  default: () => <div data-testid="spinner" />,
}));

vi.mock("@context/demande/QuestionnaireProvider", () => ({
  QuestionnaireProvider: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="questionnaire-provider">{children}</div>
  ),
}));

vi.mock("@controls/Questionnaire/TypeDemandeContent", () => ({
  TypeDemandeContent: () => <div data-testid="type-demande-content" />,
}));

describe("DemandeSaisie (route demandeur)", () => {
  beforeEach(() => vi.clearAllMocks());

  it("sans id → affiche le spinner", () => {
    mockParams = { id: undefined };
    renderWithProviders(<DemandeSaisie />);
    expect(screen.getByTestId("spinner")).toBeInTheDocument();
  });

  it("avec id → affiche le titre 'Demande'", () => {
    mockParams = { id: "123" };
    renderWithProviders(<DemandeSaisie />);
    expect(screen.getByRole("heading", { name: /demande/i, level: 1 })).toBeInTheDocument();
  });

  it("avec id → QuestionnaireProvider est monté", () => {
    mockParams = { id: "123" };
    renderWithProviders(<DemandeSaisie />);
    expect(screen.getByTestId("questionnaire-provider")).toBeInTheDocument();
  });

  it("avec id → TypeDemandeContent est rendu", () => {
    mockParams = { id: "123" };
    renderWithProviders(<DemandeSaisie />);
    expect(screen.getByTestId("type-demande-content")).toBeInTheDocument();
  });

  it("avec id → bouton 'Besoin d'aide' est présent", () => {
    mockParams = { id: "123" };
    renderWithProviders(<DemandeSaisie />);
    // Le bouton a un aria-label long; on vérifie sa présence par son texte visible
    expect(screen.getByText(/besoin d.aide/i)).toBeInTheDocument();
  });
});
