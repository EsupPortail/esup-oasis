import React from "react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import NotFound from "./NotFound";

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useNavigate: () => vi.fn() };
});

vi.mock("@utils/PageTitle/PageTitle", () => ({
  default: () => null,
}));

describe("NotFound — accessibilité (Y10)", () => {
  beforeEach(() => vi.clearAllMocks());

  it("aucune violation axe-core", async () => {
    const { container } = renderWithProviders(<NotFound />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it("contient un landmark main", () => {
    renderWithProviders(<NotFound />);
    expect(screen.getByRole("main")).toBeInTheDocument();
  });

  it("contient un titre h1 significatif", () => {
    renderWithProviders(<NotFound />);
    expect(
      screen.getByRole("heading", { level: 1, name: /page introuvable/i }),
    ).toBeInTheDocument();
  });

  it("le bouton 'Retour à l'accueil' a un nom accessible", () => {
    renderWithProviders(<NotFound />);
    expect(screen.getByRole("button", { name: /retour à l'accueil/i })).toBeInTheDocument();
  });

  it("le bouton 'Page précédente' a un nom accessible", () => {
    renderWithProviders(<NotFound />);
    expect(screen.getByRole("button", { name: /page précédente/i })).toBeInTheDocument();
  });

  it("le '404' décoratif est masqué aux technologies d'assistance", () => {
    const { container } = renderWithProviders(<NotFound />);
    // Le backdrop contient "404" et doit être aria-hidden
    const hiddenEl = container.querySelector("[aria-hidden='true']");
    expect(hiddenEl).toBeInTheDocument();
    expect(hiddenEl?.textContent).toContain("404");
  });
});
