/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React from "react";
import { describe, expect, it, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import dayjs from "dayjs";
import { IInscription } from "@api";
import { InscriptionsGroupeesItem } from "./InscriptionsGroupeesItem";

vi.mock("@controls/Items/ComposanteItem", () => ({
  ComposanteItem: ({ composanteId }: { composanteId?: string }) => (
    <span data-testid="composante">{composanteId}</span>
  ),
}));

const fin = dayjs().add(1, "year").toISOString();

function inscription(
  id: string,
  composante: string,
  libelle: string,
  principale: boolean,
  debut = "2026-09-01T00:00:00+00:00",
): IInscription {
  return { "@id": id, debut, fin, principale, formation: { composante, libelle } } as IInscription;
}

describe("InscriptionsGroupeesItem", () => {
  it("affiche un tiret sans inscription en cours", () => {
    const { container } = render(<InscriptionsGroupeesItem inscriptions={[]} />);

    expect(container.querySelector(".anticon-minus")).toBeInTheDocument();
  });

  it("ne marque pas l'étoile quand il n'y a qu'une inscription", () => {
    render(
      <InscriptionsGroupeesItem inscriptions={[inscription("/i/1", "/c/1", "Licence", true)]} />,
    );

    expect(screen.getByText("Licence")).toBeInTheDocument();
    expect(screen.queryByLabelText("Inscription principale")).not.toBeInTheDocument();
  });

  it("marque l'inscription principale d'une étoile accessible", () => {
    render(
      <InscriptionsGroupeesItem
        inscriptions={[
          inscription("/i/1", "/c/1", "Licence", false),
          inscription("/i/2", "/c/2", "Master", true),
        ]}
      />,
    );

    expect(screen.getAllByLabelText("Inscription principale")).toHaveLength(1);
    expect(screen.getAllByTestId("composante").map((c) => c.textContent)).toEqual(["/c/2", "/c/1"]);
  });

  it("regroupe les formations d'une même composante sous un seul tag", () => {
    render(
      <InscriptionsGroupeesItem
        inscriptions={[
          inscription("/i/1", "/c/1", "Licence", true),
          inscription("/i/2", "/c/1", "Mineure", false),
        ]}
      />,
    );

    expect(screen.getAllByTestId("composante")).toHaveLength(1);
    expect(screen.getByText("Licence")).toBeInTheDocument();
    expect(screen.getByText("Mineure")).toBeInTheDocument();
  });

  it("replie les autres composantes au-delà de deux inscriptions et les déplie à la demande", async () => {
    const user = userEvent.setup();
    render(
      <InscriptionsGroupeesItem
        inscriptions={[
          inscription("/i/1", "/c/1", "Licence", true),
          inscription("/i/2", "/c/2", "Master", false),
          inscription("/i/3", "/c/3", "DU", false),
        ]}
      />,
    );

    expect(screen.queryByText("Master")).not.toBeInTheDocument();

    await user.click(screen.getByRole("button", { name: "+ 2 autres inscriptions" }));

    expect(screen.getByText("Master")).toBeInTheDocument();
    expect(screen.getByText("DU")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Réduire" })).toHaveAttribute(
      "aria-expanded",
      "true",
    );
  });

  it("n'affiche pas de bouton de repli quand toutes les inscriptions sont dans la même composante", () => {
    render(
      <InscriptionsGroupeesItem
        inscriptions={[
          inscription("/i/1", "/c/1", "Licence", true),
          inscription("/i/2", "/c/1", "Mineure", false),
          inscription("/i/3", "/c/1", "Option", false),
        ]}
      />,
    );

    expect(screen.getByText("Option")).toBeInTheDocument();
    expect(screen.queryByRole("button")).not.toBeInTheDocument();
  });

  it("ne compte que les inscriptions masquées dans le bouton de repli", () => {
    render(
      <InscriptionsGroupeesItem
        inscriptions={[
          inscription("/i/1", "/c/1", "Licence", true),
          inscription("/i/2", "/c/1", "Mineure", false),
          inscription("/i/3", "/c/2", "Master", false),
        ]}
      />,
    );

    expect(screen.getByRole("button", { name: "+ 1 autre inscription" })).toBeInTheDocument();
  });
});
