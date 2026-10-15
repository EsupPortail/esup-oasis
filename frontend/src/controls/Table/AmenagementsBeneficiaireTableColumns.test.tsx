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
import { ColumnsType } from "antd/lib/table";
import { Utilisateur } from "@lib";
import {
  buildAmenagementsBenefDatasource,
  IAmenagementsBeneficiaireTableDataSource,
} from "./AmenagementsBeneficiaireTable";
import { amenagementsBeneficiaireTableColumns } from "./AmenagementsBeneficiaireTableColumns";

vi.mock("@controls/Items/ComposanteItem", () => ({
  ComposanteItem: ({ composanteId }: { composanteId?: string }) => (
    <span data-testid="composante">{composanteId}</span>
  ),
}));

const fin = dayjs().add(1, "year").toISOString();

const inscription = (n: number, composante: string, principale: boolean) => ({
  "@id": `/inscriptions/${n}`,
  debut: "2026-09-01T00:00:00+00:00",
  fin,
  principale,
  formation: { composante, libelle: `Formation ${n}` },
});

const beneficiaire = (inscriptions: unknown[]) => ({
  "@id": "/amenagements/utilisateurs/1",
  uid: "ben1@test.fr",
  nom: "Nom1",
  prenom: "Prenom1",
  email: "ben1@test.fr",
  tags: [],
  inscriptions,
  amenagements: [],
});

function ligne(inscriptions: unknown[]): IAmenagementsBeneficiaireTableDataSource {
  return buildAmenagementsBenefDatasource([beneficiaire(inscriptions)] as never, [])[0];
}

// Les colonnes utilisent des hooks (FilterProps) : elles doivent être construites pendant un rendu.
function Colonnes({
  record,
  onColonnes,
}: {
  record?: IAmenagementsBeneficiaireTableDataSource;
  onColonnes?: (colonnes: ColumnsType<IAmenagementsBeneficiaireTableDataSource>) => void;
}) {
  const colonnes = amenagementsBeneficiaireTableColumns({
    filtre: { page: 1, itemsPerPage: 25 },
    setFiltre: vi.fn(),
    typesAmenagements: [],
    categoriesAmenagements: [],
    navigate: vi.fn(),
    user: { isGestionnaire: false } as Utilisateur,
    fixPremiereColonne: false,
  });
  onColonnes?.(colonnes);
  const colonne = colonnes.find((c) => "title" in c && c.title === "Inscription");
  if (!record || !colonne || !("render" in colonne) || !colonne.render) return null;
  return <>{colonne.render(undefined, record, 0)}</>;
}

const renderColonneInscription = (record: IAmenagementsBeneficiaireTableDataSource) =>
  render(<Colonnes record={record} />);

describe("amenagementsBeneficiaireTableColumns — colonne Inscription", () => {
  it("n'affiche qu'une seule colonne Inscription", () => {
    let colonnes: ColumnsType<IAmenagementsBeneficiaireTableDataSource> = [];
    render(<Colonnes onColonnes={(c) => (colonnes = c)} />);

    expect(colonnes.filter((c) => "title" in c && c.title === "Inscription")).toHaveLength(1);
  });

  it("affiche toutes les inscriptions de la ligne, et non la seule première", () => {
    renderColonneInscription(
      ligne([inscription(1, "/composantes/1", false), inscription(2, "/composantes/2", true)]),
    );

    expect(screen.getAllByTestId("composante").map((c) => c.textContent)).toEqual([
      "/composantes/2",
      "/composantes/1",
    ]);
  });

  it("affiche un tiret sans inscription en cours", () => {
    const { container } = renderColonneInscription(ligne([]));

    expect(container.querySelector(".anticon-minus")).toBeInTheDocument();
  });
});

describe("buildAmenagementsBenefDatasource — inscriptions", () => {
  it("conserve toutes les inscriptions du bénéficiaire dans l'ordre reçu", () => {
    const inscriptions = [
      inscription(1, "/composantes/1", false),
      inscription(2, "/composantes/2", true),
    ];

    expect(ligne(inscriptions).inscriptions).toEqual(inscriptions);
  });
});
