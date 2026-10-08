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
import userEvent from "@testing-library/user-event";
import { describe, it, expect, vi } from "vitest";
import { IUtilisateur } from "@api";
import { renderWithProviders } from "@/test";
import { ScolariteSection } from "./ScolariteSection";

vi.mock("@controls/TabsContent/TabScolarite", () => ({
  ScolariteListItem: ({ inscription }: { inscription: { formation?: { libelle?: string } } }) => (
    <div data-testid="inscription">{inscription.formation?.libelle}</div>
  ),
}));

/** Encadre la date du jour, donc toujours "en cours". */
const EN_COURS = { debut: "2000-01-01", fin: "2100-12-31" };
/** Entièrement dans le passé, donc toujours "précédente". */
const TERMINEE = { debut: "2000-01-01", fin: "2000-12-31" };

const rendre = (utilisateur: Partial<IUtilisateur>, isFetching = false) =>
  renderWithProviders(
    <ScolariteSection utilisateur={utilisateur as IUtilisateur} isFetching={isFetching} />,
  );

const libelles = () => screen.getAllByTestId("inscription").map((n) => n.textContent);

const boutonPrecedentes = () => screen.queryByRole("button", { name: /Inscriptions précédentes/ });

describe("ScolariteSection", () => {
  it("affiche le numéro étudiant, le régime et le bouton de copie", () => {
    rendre({ numeroEtudiant: 12345678, statutEtudiant: "Formation initiale" });
    expect(screen.getByText("12345678")).toBeInTheDocument();
    expect(screen.getByText("Formation initiale")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /copy|copier/i })).toBeInTheDocument();
  });

  it.each([
    ["undefined", undefined],
    ["null", null],
  ])("affiche un tiret sans bouton de copie si le numéro étudiant est %s", (_nom, numero) => {
    rendre({ numeroEtudiant: numero });
    expect(screen.getByRole("img", { name: "minus" })).toBeInTheDocument();
    expect(screen.queryByRole("button", { name: /copy|copier/i })).not.toBeInTheDocument();
  });

  it("n'affiche que les inscriptions en cours par défaut", () => {
    rendre({
      inscriptions: [
        { "@id": "/i/1", ...TERMINEE, formation: { libelle: "Ancienne" } },
        { "@id": "/i/2", ...EN_COURS, formation: { libelle: "Actuelle" } },
      ],
    } as Partial<IUtilisateur>);
    expect(libelles()).toEqual(["Actuelle"]);
  });

  it("trie les inscriptions en cours par début décroissant", () => {
    rendre({
      inscriptions: [
        {
          "@id": "/i/1",
          debut: "2021-09-01",
          fin: "2100-12-31",
          formation: { libelle: "Ancienne" },
        },
        {
          "@id": "/i/3",
          debut: "2023-09-01",
          fin: "2100-12-31",
          formation: { libelle: "Récente" },
        },
        {
          "@id": "/i/2",
          debut: "2022-09-01",
          fin: "2100-12-31",
          formation: { libelle: "Médiane" },
        },
      ],
    } as Partial<IUtilisateur>);
    expect(libelles()).toEqual(["Récente", "Médiane", "Ancienne"]);
  });

  it("affiche un message si aucune inscription en cours", () => {
    rendre({
      inscriptions: [{ "@id": "/i/1", ...TERMINEE, formation: { libelle: "Ancienne" } }],
    } as Partial<IUtilisateur>);
    expect(screen.getByText("Aucune inscription en cours")).toBeInTheDocument();
    expect(screen.queryByTestId("inscription")).not.toBeInTheDocument();
  });

  it("masque le bouton s'il n'y a aucune inscription précédente", () => {
    rendre({
      inscriptions: [{ "@id": "/i/1", ...EN_COURS, formation: { libelle: "Actuelle" } }],
    } as Partial<IUtilisateur>);
    expect(boutonPrecedentes()).not.toBeInTheDocument();
  });

  it("affiche le nombre d'inscriptions précédentes sur le bouton", () => {
    rendre({
      inscriptions: [
        { "@id": "/i/1", ...TERMINEE, formation: { libelle: "Ancienne" } },
        { "@id": "/i/2", ...TERMINEE, formation: { libelle: "Très ancienne" } },
      ],
    } as Partial<IUtilisateur>);
    expect(boutonPrecedentes()).toHaveTextContent("Inscriptions précédentes (2)");
  });

  it("affiche puis masque les inscriptions précédentes au clic sur le bouton", async () => {
    rendre({
      inscriptions: [
        { "@id": "/i/1", ...TERMINEE, formation: { libelle: "Ancienne" } },
        { "@id": "/i/2", ...EN_COURS, formation: { libelle: "Actuelle" } },
      ],
    } as Partial<IUtilisateur>);

    const bouton = boutonPrecedentes() as HTMLElement;
    expect(bouton).toHaveAttribute("aria-expanded", "false");

    await userEvent.click(bouton);
    expect(libelles()).toEqual(["Actuelle", "Ancienne"]);
    expect(bouton).toHaveAttribute("aria-expanded", "true");

    await userEvent.click(bouton);
    expect(libelles()).toEqual(["Actuelle"]);
    expect(bouton).toHaveAttribute("aria-expanded", "false");
  });

  it("ne plante pas sans inscriptions", () => {
    rendre({});
    expect(screen.queryByTestId("inscription")).not.toBeInTheDocument();
    expect(screen.getByText("Aucune inscription en cours")).toBeInTheDocument();
    expect(boutonPrecedentes()).not.toBeInTheDocument();
  });

  it("affiche un skeleton pendant le chargement", () => {
    rendre({ numeroEtudiant: 12345678 }, true);
    expect(screen.getByRole("heading", { name: "Scolarité" })).toBeVisible();
    expect(screen.queryByText("12345678")).not.toBeInTheDocument();
  });
});
