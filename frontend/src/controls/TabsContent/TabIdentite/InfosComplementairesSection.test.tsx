/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { screen } from "@testing-library/react";
import { describe, it, expect, vi, afterEach } from "vitest";
import { IUtilisateur } from "@api";
import { renderWithProviders } from "@/test";
import { InfosComplementairesSection } from "./InfosComplementairesSection";

type InfosComplementaires = NonNullable<IUtilisateur["infosComplementaires"]>;

const utilisateurAvec = (infos?: InfosComplementaires): IUtilisateur =>
  ({ infosComplementaires: infos }) as IUtilisateur;

const rendre = (infos?: InfosComplementaires, isFetching = false) =>
  renderWithProviders(
    <InfosComplementairesSection utilisateur={utilisateurAvec(infos)} isFetching={isFetching} />,
  );

describe("InfosComplementairesSection", () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("ne rend rien si infosComplementaires est absent", () => {
    rendre(undefined);
    expect(screen.queryByText("Informations complémentaires")).not.toBeInTheDocument();
  });

  it("ne rend rien si infosComplementaires est vide", () => {
    rendre([]);
    expect(screen.queryByText("Informations complémentaires")).not.toBeInTheDocument();
  });

  it("affiche le titre, les libellés et les valeurs", () => {
    rendre([
      { libelle: "Régime", valeur: "Salarié" },
      { libelle: "Bourse", valeur: "Échelon 3" },
    ]);
    expect(screen.getByRole("heading", { name: "Informations complémentaires" })).toBeVisible();
    expect(screen.getByText("Régime")).toBeInTheDocument();
    expect(screen.getByText("Salarié")).toBeInTheDocument();
    expect(screen.getByText("Bourse")).toBeInTheDocument();
    expect(screen.getByText("Échelon 3")).toBeInTheDocument();
  });

  it("affiche un skeleton et masque les valeurs pendant le chargement", () => {
    const { container } = rendre([{ libelle: "Régime", valeur: "Salarié" }], true);
    expect(screen.getByRole("heading", { name: "Informations complémentaires" })).toBeVisible();
    expect(container.querySelector(".ant-skeleton")).toBeInTheDocument();
    expect(screen.queryByText("Salarié")).not.toBeInTheDocument();
  });

  it("n'affiche pas de skeleton hors chargement", () => {
    const { container } = rendre([{ libelle: "Régime", valeur: "Salarié" }]);
    expect(container.querySelector(".ant-skeleton")).not.toBeInTheDocument();
  });

  it.each([
    ["undefined", undefined],
    ["chaîne vide", ""],
  ])("affiche un tiret et pas de bouton de copie si la valeur est %s", (_nom, valeur) => {
    rendre([{ libelle: "Régime", valeur }]);
    expect(screen.getByRole("img", { name: "minus" })).toBeInTheDocument();
    expect(screen.queryByRole("button", { name: /copy|copier/i })).not.toBeInTheDocument();
  });

  it("propose la copie quand la valeur est renseignée", () => {
    rendre([{ libelle: "Régime", valeur: "Salarié" }]);
    expect(screen.getByRole("button", { name: /copy|copier/i })).toBeInTheDocument();
  });

  it.each([
    ["undefined", undefined],
    ["chaîne vide", ""],
  ])("utilise « Information » comme libellé de repli si libelle est %s", (_nom, libelle) => {
    rendre([{ libelle, valeur: "x" }]);
    expect(screen.getByText("Information")).toBeInTheDocument();
  });

  it("n'émet pas de warning React sur les clés", () => {
    const erreur = vi.spyOn(console, "error").mockImplementation(() => undefined);
    rendre([
      { libelle: "A", valeur: "1" },
      { libelle: "A", valeur: "2" },
    ]);
    const warningsCle = erreur.mock.calls.filter((args) => String(args[0]).includes("key"));
    expect(warningsCle).toHaveLength(0);
  });
});
