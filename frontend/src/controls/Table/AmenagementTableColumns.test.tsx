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
import dayjs from "dayjs";
import { IAmenagement } from "@api";
import { amenagementTableColumns } from "./AmenagementTableColumns";

vi.mock("@controls/Items/ComposanteItem", () => ({
  ComposanteItem: ({ composanteId }: { composanteId?: string }) => (
    <span data-testid="composante">{composanteId}</span>
  ),
}));

const fin = dayjs().add(1, "year").toISOString();

const amenagement = (inscriptions: unknown[]): IAmenagement =>
  ({
    "@id": "/amenagements/1",
    beneficiaire: { "@id": "/utilisateurs/ben", inscriptions },
  }) as unknown as IAmenagement;

// Les colonnes utilisent des hooks (FilterProps) : elles doivent être construites pendant un rendu.
function ColonneInscription({ record }: { record: IAmenagement }) {
  const colonnes = amenagementTableColumns({
    filtre: { page: 1, itemsPerPage: 25 },
    setFiltre: vi.fn(),
    navigate: vi.fn(),
  });
  const colonne = colonnes.find((c) => "title" in c && c.title === "Inscription");
  if (!colonne || !("render" in colonne) || !colonne.render) {
    throw new Error("Colonne « Inscription » introuvable");
  }
  return <>{colonne.render(undefined, record, 0)}</>;
}

const renderColonneInscription = (record: IAmenagement) =>
  render(<ColonneInscription record={record} />);

describe("amenagementTableColumns — colonne Inscription", () => {
  it("affiche toutes les inscriptions du bénéficiaire, regroupées par composante", () => {
    renderColonneInscription(
      amenagement([
        {
          "@id": "/i/1",
          debut: "2026-09-01T00:00:00+00:00",
          fin,
          principale: true,
          formation: { composante: "/composantes/1", libelle: "Licence" },
        },
        {
          "@id": "/i/2",
          debut: "2026-09-01T00:00:00+00:00",
          fin,
          principale: false,
          formation: { composante: "/composantes/2", libelle: "Master" },
        },
      ]),
    );

    expect(screen.getAllByTestId("composante").map((c) => c.textContent)).toEqual([
      "/composantes/1",
      "/composantes/2",
    ]);
  });

  it("affiche un tiret quand le bénéficiaire n'a pas d'inscription en cours", () => {
    const { container } = renderColonneInscription(amenagement([]));

    expect(container.querySelector(".anticon-minus")).toBeInTheDocument();
  });
});
