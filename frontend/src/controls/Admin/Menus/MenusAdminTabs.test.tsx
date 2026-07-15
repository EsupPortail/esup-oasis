/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { screen } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { renderWithProviders } from "@/test";
import MenusAdminTabs from "@controls/Admin/Menus/MenusAdminTabs";

const { mockUseGetFullCollection, mockUseAuth } = vi.hoisted(() => ({
  mockUseGetFullCollection: vi.fn(),
  mockUseAuth: vi.fn((): { user: { isAdminTechnique?: boolean } | undefined } => ({
    user: undefined,
  })),
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetFullCollection: mockUseGetFullCollection,
    useGetItem: () => ({ data: undefined, isFetching: false }),
    useGetCollection: () => ({ data: undefined, isFetching: false }),
    usePatch: () => ({ mutate: vi.fn() }),
    usePost: () => ({ mutate: vi.fn() }),
  }),
}));

vi.mock("@/auth/AuthProvider", () => ({
  useAuth: () => mockUseAuth(),
}));

function makeParametre(cle: string, valeur: string) {
  return {
    "@id": `/parametres/${cle}`,
    cle,
    valeursCourantes: [{ "@id": `/parametres/${cle}/valeurs/1`, valeur }],
  };
}

describe("MenusAdminTabs", () => {
  it("affiche un skeleton pendant le chargement, pas les onglets", () => {
    mockUseGetFullCollection.mockReturnValue({ data: undefined, isFetching: true });
    const { container } = renderWithProviders(<MenusAdminTabs />);

    expect(container.querySelector(".ant-skeleton")).toBeInTheDocument();
    expect(screen.queryByRole("tab")).not.toBeInTheDocument();
  });

  it("affiche les 9 onglets attendus une fois les paramètres chargés", () => {
    mockUseGetFullCollection.mockReturnValue({
      data: { items: [makeParametre("MENU_THEME", "Thème")] },
      isFetching: false,
    });
    renderWithProviders(<MenusAdminTabs />);

    [
      "Gestionnaire",
      "Renfort",
      "Demandeur",
      "Bénéficiaire",
      "Intervenant",
      "Membre de commission",
      "Référent de composante",
      "Administrateur",
      "Commun",
    ].forEach((label) => {
      expect(screen.getByRole("tab", { name: label })).toBeInTheDocument();
    });
  });

  it("l'onglet actif n'affiche que les clés qui lui appartiennent", () => {
    mockUseGetFullCollection.mockReturnValue({
      data: {
        items: [
          makeParametre("MENU_THEME", "Thème"),
          makeParametre("MENU_PLANNING_PLANIF", "Planning"),
        ],
      },
      isFetching: false,
    });
    renderWithProviders(<MenusAdminTabs />);

    // Onglet "Gestionnaire" actif par défaut (premier onglet Tabs Ant Design) :
    // son panneau contient "Planning" mais pas "Thème" (onglet "Commun").
    const panneauActif = screen.getByRole("tabpanel", { name: "Gestionnaire" });
    expect(panneauActif).toHaveTextContent("Planning");
    expect(panneauActif).not.toHaveTextContent("Thème");
  });

  it("n'affiche la clé technique dans l'arbre d'édition que pour un administrateur technique", () => {
    mockUseGetFullCollection.mockReturnValue({
      data: { items: [makeParametre("MENU_PLANNING_PLANIF", "Planning")] },
      isFetching: false,
    });
    mockUseAuth.mockReturnValueOnce({ user: undefined });
    const { container, rerender } = renderWithProviders(<MenusAdminTabs />);
    expect(container.querySelector(".menu-label-node__cle")).not.toBeInTheDocument();

    mockUseAuth.mockReturnValueOnce({ user: { isAdminTechnique: true } });
    rerender(<MenusAdminTabs />);
    expect(screen.getByText("MENU_PLANNING_PLANIF")).toBeInTheDocument();
  });
});
