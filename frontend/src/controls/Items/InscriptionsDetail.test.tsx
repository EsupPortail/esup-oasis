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
import { InscriptionsDetail } from "./InscriptionsDetail";

vi.mock("@controls/Items/ComposanteItem", () => ({
  ComposanteItem: ({ composanteId }: { composanteId?: string }) => (
    <span data-testid="composante">{composanteId}</span>
  ),
}));

const debutEnCours = dayjs().subtract(1, "month").toISOString();
const finEnCours = dayjs().add(1, "year").toISOString();
const debutPasse = dayjs().subtract(2, "year").toISOString();
const finPasse = dayjs().subtract(1, "year").toISOString();

function enCours(id: string, composante: string, libelle: string, principale: boolean) {
  return {
    "@id": id,
    debut: debutEnCours,
    fin: finEnCours,
    principale,
    formation: { composante, libelle },
  } as IInscription;
}

function passee(id: string, composante: string, libelle: string) {
  return {
    "@id": id,
    debut: debutPasse,
    fin: finPasse,
    principale: false,
    formation: { composante, libelle },
  } as IInscription;
}

describe("InscriptionsDetail", () => {
  it("affiche un message sans aucune inscription", () => {
    render(<InscriptionsDetail inscriptions={[]} />);

    expect(screen.getByText("Aucune inscription")).toBeInTheDocument();
  });

  it("ne marque pas de principale quand il n'y a qu'une inscription en cours", () => {
    render(<InscriptionsDetail inscriptions={[enCours("/i/1", "/c/1", "Licence", true)]} />);

    expect(screen.getByText("Licence")).toBeInTheDocument();
    expect(screen.queryByText("Principale")).not.toBeInTheDocument();
  });

  it("met en avant l'unique inscription en cours, sans mention « Principale »", () => {
    render(<InscriptionsDetail inscriptions={[enCours("/i/1", "/c/1", "Licence", false)]} />);

    expect(screen.getByText("Licence").closest("strong")).toBeInTheDocument();
    expect(screen.queryByText("Principale")).not.toBeInTheDocument();
  });

  it("ne met en avant que la principale quand il y a plusieurs inscriptions en cours", () => {
    render(
      <InscriptionsDetail
        inscriptions={[
          enCours("/i/1", "/c/1", "Licence", false),
          enCours("/i/2", "/c/2", "Master", true),
        ]}
      />,
    );

    expect(screen.getByText("Master").closest("strong")).toBeInTheDocument();
    expect(screen.getByText("Licence").closest("strong")).not.toBeInTheDocument();
  });

  it("marque la principale et la place avant les autres", () => {
    render(
      <InscriptionsDetail
        inscriptions={[
          enCours("/i/1", "/c/1", "Licence", false),
          enCours("/i/2", "/c/2", "Master", true),
        ]}
      />,
    );

    expect(screen.getAllByText("Principale")).toHaveLength(1);
    const [premiere, seconde] = screen.getAllByTestId("composante");
    expect(premiere).toHaveTextContent("/c/2");
    expect(seconde).toHaveTextContent("/c/1");
  });

  it("indique l'absence d'inscription en cours et replie l'historique", async () => {
    render(<InscriptionsDetail inscriptions={[passee("/i/1", "/c/1", "Ancienne licence")]} />);

    expect(screen.getByText("Aucune inscription en cours")).toBeInTheDocument();
    expect(screen.getByText("Historique (1)")).toBeInTheDocument();
    expect(screen.queryByText("Ancienne licence")).not.toBeInTheDocument();

    await userEvent.click(screen.getByText("Historique (1)"));

    expect(await screen.findByText("Ancienne licence")).toBeInTheDocument();
  });

  it("regroupe l'historique par année universitaire", async () => {
    render(
      <InscriptionsDetail
        inscriptions={[
          enCours("/i/1", "/c/1", "Licence", true),
          passee("/i/2", "/c/1", "L1"),
          passee("/i/3", "/c/2", "M1"),
        ]}
      />,
    );

    await userEvent.click(screen.getByText("Historique (2)"));

    const debut = dayjs(debutPasse);
    const anneeDebut = debut.month() >= 7 ? debut.year() : debut.year() - 1;
    expect(await screen.findByText(`${anneeDebut}-${anneeDebut + 1}`)).toBeInTheDocument();
  });
});
