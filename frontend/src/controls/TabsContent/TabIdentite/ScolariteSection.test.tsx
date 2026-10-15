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
import { describe, it, expect, vi } from "vitest";
import { IInscription, IUtilisateur } from "@api";
import { renderWithProviders } from "@/test";
import { ScolariteSection } from "./ScolariteSection";

vi.mock("@controls/Items/InscriptionsDetail", () => ({
  InscriptionsDetail: ({ inscriptions }: { inscriptions?: IInscription[] }) => (
    <div data-testid="inscriptions-detail">{inscriptions?.length ?? 0} inscriptions</div>
  ),
}));

const rendre = (utilisateur: Partial<IUtilisateur>, isFetching = false) =>
  renderWithProviders(
    <ScolariteSection utilisateur={utilisateur as IUtilisateur} isFetching={isFetching} />,
  );

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

  it("transmet les inscriptions au composant InscriptionsDetail", () => {
    rendre({
      inscriptions: [
        { "@id": "/i/1", debut: "2021-09-01", formation: { libelle: "Ancienne" } },
        { "@id": "/i/2", debut: "2023-09-01", formation: { libelle: "Récente" } },
      ],
    } as Partial<IUtilisateur>);
    expect(screen.getByTestId("inscriptions-detail")).toHaveTextContent("2 inscriptions");
  });

  it("ne plante pas sans inscriptions", () => {
    rendre({});
    expect(screen.getByTestId("inscriptions-detail")).toHaveTextContent("0 inscriptions");
  });

  it("affiche un skeleton pendant le chargement", () => {
    rendre({ numeroEtudiant: 12345678 }, true);
    expect(screen.getByRole("heading", { name: "Scolarité" })).toBeVisible();
    expect(screen.queryByText("12345678")).not.toBeInTheDocument();
  });
});
