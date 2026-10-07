import React from "react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
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
  default: () => <div role="status" aria-label="Chargement en cours" />,
}));

vi.mock("@context/demande/QuestionnaireProvider", () => ({
  QuestionnaireProvider: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="questionnaire-provider">{children}</div>
  ),
}));

vi.mock("@controls/Questionnaire/TypeDemandeContent", () => ({
  TypeDemandeContent: () => (
    <section aria-label="Questionnaire">
      <h2>Questionnaire</h2>
    </section>
  ),
}));

// ─── Y16 : DemandeSaisie — accessibilité, étape 1 ────────────────────────────

describe("DemandeSaisie — accessibilité (Y16)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockParams = { id: "123" };
  });

  it("aucune violation axe-core avec id valide", async () => {
    const { container } = renderWithProviders(<DemandeSaisie />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it("la page contient un titre h1 'Demande'", () => {
    renderWithProviders(<DemandeSaisie />);
    expect(screen.getByRole("heading", { level: 1, name: /demande/i })).toBeInTheDocument();
  });

  it("le bouton 'Besoin d'aide' a un aria-label explicite non vide", () => {
    renderWithProviders(<DemandeSaisie />);
    // L'aria-label surcharge le nom accessible du bouton ("Envoyer un email… pour obtenir de l'aide")
    const btn = screen.getByRole("button", { name: /pour obtenir de l.aide/i });
    expect(btn).toBeInTheDocument();
    expect(btn).toHaveAttribute("aria-label");
    expect(btn.getAttribute("aria-label")).not.toBe("");
  });

  it("QuestionnaireProvider est monté sans erreur", () => {
    renderWithProviders(<DemandeSaisie />);
    expect(screen.getByTestId("questionnaire-provider")).toBeInTheDocument();
  });

  it("TypeDemandeContent est rendu à l'intérieur du QuestionnaireProvider", () => {
    renderWithProviders(<DemandeSaisie />);
    expect(screen.getByRole("region", { name: /questionnaire/i })).toBeInTheDocument();
  });

  it("sans id — aucune violation axe-core (spinner)", async () => {
    mockParams = { id: undefined };
    const { container } = renderWithProviders(<DemandeSaisie />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
