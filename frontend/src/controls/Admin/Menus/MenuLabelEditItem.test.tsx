/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import { IParametre } from "@api";
import MenuLabelEditItem from "@controls/Admin/Menus/MenuLabelEditItem";

const { mockMutate, mockMutatePost, mockMessageSuccess, mockMessageWarning } = vi.hoisted(() => ({
  mockMutate: vi.fn(),
  mockMutatePost: vi.fn(),
  mockMessageSuccess: vi.fn(),
  mockMessageWarning: vi.fn(),
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    usePatch: () => ({ mutate: mockMutate }),
    usePost: () => ({ mutate: mockMutatePost }),
  }),
}));

vi.mock("antd", async (importOriginal) => {
  const actual = await importOriginal<typeof import("antd")>();
  return {
    ...actual,
    App: {
      ...actual.App,
      useApp: () => ({
        message: { success: mockMessageSuccess, warning: mockMessageWarning },
      }),
    },
  };
});

function makeParametre(valeur?: string): IParametre | undefined {
  if (valeur === undefined) return undefined;
  return {
    "@id": "/parametres/MENU_PLANNING_PLANIF",
    cle: "MENU_PLANNING_PLANIF",
    valeursCourantes: [{ "@id": "/parametres/MENU_PLANNING_PLANIF/valeurs/1", valeur }],
  } as IParametre;
}

// Paramètre existant (a un "@id") mais sans valeur courante (ex. valeur expirée/supprimée) :
// éditer doit créer (POST) la première valeur, pas tenter un PATCH sur une ressource inexistante.
function makeParametreSansValeur(): IParametre {
  return {
    "@id": "/parametres/MENU_PLANNING_PLANIF",
    "@type": "Parametre",
    cle: "MENU_PLANNING_PLANIF",
    valeursCourantes: [],
  } as IParametre;
}

describe("MenuLabelEditItem", () => {
  beforeEach(() => vi.clearAllMocks());

  it("affiche la valeur courante du paramètre", () => {
    renderWithProviders(
      <MenuLabelEditItem
        parametre={makeParametre("Planning")}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        afficherCle={false}
      />,
    );
    expect(screen.getByText("Planning")).toBeInTheDocument();
  });

  it("affiche la condition si fournie", () => {
    renderWithProviders(
      <MenuLabelEditItem
        parametre={makeParametre("Planning")}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        condition="Visible si Gestionnaire"
        niveau={0}
        afficherCle={false}
      />,
    );
    expect(screen.getByText(/Visible si Gestionnaire/)).toBeInTheDocument();
  });

  it("affiche le libellé par défaut (pas 'Non configuré') si le paramètre n'a pas de valeur courante", () => {
    renderWithProviders(
      <MenuLabelEditItem
        parametre={undefined}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        afficherCle={false}
      />,
    );
    expect(screen.getByText("Planning")).toBeInTheDocument();
    expect(screen.queryByText(/non configuré/i)).not.toBeInTheDocument();
    expect(mockMutate).not.toHaveBeenCalled();
  });

  it("n'affiche la clé technique que si afficherCle est vrai (réservé aux admins techniques)", () => {
    const { rerender, container } = renderWithProviders(
      <MenuLabelEditItem
        parametre={makeParametre("Planning")}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        afficherCle={false}
      />,
    );
    expect(container.querySelector(".menu-label-node__cle")).not.toBeInTheDocument();

    rerender(
      <MenuLabelEditItem
        parametre={makeParametre("Planning")}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        afficherCle
      />,
    );
    expect(screen.getByText("MENU_PLANNING_PLANIF")).toBeInTheDocument();
  });

  it("édite la valeur puis déclenche le PATCH avec le bon @id et la nouvelle valeur", async () => {
    const user = userEvent.setup();
    renderWithProviders(
      <MenuLabelEditItem
        parametre={makeParametre("Planning")}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        afficherCle={false}
      />,
    );

    await user.click(screen.getByRole("button", { name: "Edit" }));
    const input = screen.getByRole("textbox");
    await user.clear(input);
    await user.type(input, "Mon planning");
    await user.tab();

    expect(mockMutate).toHaveBeenCalledWith({
      "@id": "/parametres/MENU_PLANNING_PLANIF/valeurs/1",
      data: { valeur: "Mon planning" },
    });
  });

  it("trim les espaces superflus avant d'envoyer la valeur au PATCH", async () => {
    const user = userEvent.setup();
    renderWithProviders(
      <MenuLabelEditItem
        parametre={makeParametre("Planning")}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        afficherCle={false}
      />,
    );

    await user.click(screen.getByRole("button", { name: "Edit" }));
    const input = screen.getByRole("textbox");
    await user.clear(input);
    await user.type(input, "  Mon planning  ");
    await user.tab();

    expect(mockMutate).toHaveBeenCalledWith({
      "@id": "/parametres/MENU_PLANNING_PLANIF/valeurs/1",
      data: { valeur: "Mon planning" },
    });
  });

  it("ne déclenche pas le PATCH si la nouvelle valeur est vide", async () => {
    const user = userEvent.setup();
    renderWithProviders(
      <MenuLabelEditItem
        parametre={makeParametre("Planning")}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        afficherCle={false}
      />,
    );

    await user.click(screen.getByRole("button", { name: "Edit" }));
    const input = screen.getByRole("textbox");
    await user.clear(input);
    await user.tab();

    expect(mockMutate).not.toHaveBeenCalled();
    expect(mockMessageWarning).toHaveBeenCalledWith("Le libellé ne peut pas être vide.");
  });

  it("crée (POST) la première valeur quand le paramètre existe mais n'a pas de valeur courante", async () => {
    const user = userEvent.setup();
    renderWithProviders(
      <MenuLabelEditItem
        parametre={makeParametreSansValeur()}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        afficherCle={false}
      />,
    );

    await user.click(screen.getByRole("button", { name: "Edit" }));
    const input = screen.getByRole("textbox");
    await user.clear(input);
    await user.type(input, "Mon planning");
    await user.tab();

    expect(mockMutate).not.toHaveBeenCalled();
    expect(mockMutatePost).toHaveBeenCalledTimes(1);
    expect(mockMutatePost.mock.calls[0][0].data.valeur).toBe("Mon planning");
    expect(typeof mockMutatePost.mock.calls[0][0].data.debut).toBe("string");
  });

  it("n'envoie rien si le paramètre lui-même n'existe pas (aucun @id à cibler)", async () => {
    const user = userEvent.setup();
    renderWithProviders(
      <MenuLabelEditItem
        parametre={undefined}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        afficherCle={false}
      />,
    );

    await user.click(screen.getByRole("button", { name: "Edit" }));
    const input = screen.getByRole("textbox");
    await user.clear(input);
    await user.type(input, "Mon planning");
    await user.tab();

    expect(mockMutate).not.toHaveBeenCalled();
    expect(mockMutatePost).not.toHaveBeenCalled();
  });

  it("notifie une surcharge locale via onChange dès la saisie, pour l'aperçu live", async () => {
    const onChange = vi.fn();
    const user = userEvent.setup();
    renderWithProviders(
      <MenuLabelEditItem
        parametre={makeParametre("Planning")}
        cle="MENU_PLANNING_PLANIF"
        defaut="Planning"
        niveau={0}
        onChange={onChange}
        afficherCle={false}
      />,
    );

    await user.click(screen.getByRole("button", { name: "Edit" }));
    const input = screen.getByRole("textbox");
    await user.clear(input);
    await user.type(input, "Mon planning");
    await user.tab();

    expect(onChange).toHaveBeenCalledWith("MENU_PLANNING_PLANIF", "Mon planning");
  });
});
