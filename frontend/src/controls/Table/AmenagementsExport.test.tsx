/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { describe, expect, it } from "vitest";
import { IAmenagement, IComposante } from "@api";
import { getAmenagementsData } from "./AmenagementTableExport";
import { formatAmenagementsBeneficiaireData } from "./AmenagementsBeneficiaireTableExport";

const FIN = "2999-08-31T00:00:00+00:00";

const composantes = [
  { "@id": "/composantes/1", libelle: "Sciences" },
  { "@id": "/composantes/2", libelle: 'Droit "public"' },
] as IComposante[];

const amenagement = (composante: string | undefined, libelle = "Licence") =>
  ({
    "@id": "/amenagements/1",
    typeAmenagement: "/types_amenagements/1",
    beneficiaire: {
      nom: "dupont",
      inscriptions: composante ? [{ fin: FIN, formation: { composante, libelle } }] : [],
    },
  }) as unknown as IAmenagement;

const exportAmenagements = (am: IAmenagement, comps?: IComposante[]) =>
  getAmenagementsData(
    [am],
    undefined,
    undefined,
    undefined,
    undefined,
    undefined,
    comps,
    "en_cours",
  )[0];

describe("getAmenagementsData — composante", () => {
  it("résout le libellé de la composante à partir de l'IRI de l'inscription", () => {
    expect(exportAmenagements(amenagement("/composantes/1"), composantes).composante).toBe(
      "Sciences",
    );
  });

  it("échappe les guillemets du libellé pour le CSV", () => {
    expect(exportAmenagements(amenagement("/composantes/2"), composantes).composante).toBe(
      'Droit ""public""',
    );
  });

  it("laisse la composante vide si l'IRI est inconnue", () => {
    expect(
      exportAmenagements(amenagement("/composantes/99"), composantes).composante,
    ).toBeUndefined();
  });

  it("laisse la composante vide tant que les composantes ne sont pas chargées", () => {
    expect(exportAmenagements(amenagement("/composantes/1")).composante).toBeUndefined();
  });

  it("laisse composante et formation vides sans inscription", () => {
    const ligne = exportAmenagements(amenagement(undefined), composantes);

    expect(ligne.composante).toBeUndefined();
    expect(ligne.formation).toBeUndefined();
  });

  it("exporte le libellé de la formation", () => {
    expect(exportAmenagements(amenagement("/composantes/1", "Master"), composantes).formation).toBe(
      "Master",
    );
  });
});

describe("formatAmenagementsBeneficiaireData — composante", () => {
  const ligne = (composante: string | undefined) => ({
    key: "/amenagements/utilisateurs/1",
    nom: "dupont",
    tags: [],
    inscriptions: composante ? [{ fin: FIN, formation: { composante, libelle: "Licence" } }] : [],
  });

  it("résout le libellé de la composante à partir de l'IRI de l'inscription", () => {
    const [resultat] = formatAmenagementsBeneficiaireData(
      [ligne("/composantes/1")],
      [],
      [],
      composantes,
      "en_cours",
    );

    expect(resultat.composante).toBe("Sciences");
    expect(resultat.formation).toBe("Licence");
  });

  it("laisse la composante vide si les composantes ne sont pas chargées ou sans inscription", () => {
    const [sansComposantes] = formatAmenagementsBeneficiaireData(
      [ligne("/composantes/1")],
      [],
      [],
      undefined,
      "en_cours",
    );
    const [sansInscription] = formatAmenagementsBeneficiaireData(
      [ligne(undefined)],
      [],
      [],
      composantes,
      "en_cours",
    );

    expect(sansComposantes.composante).toBeUndefined();
    expect(sansInscription.composante).toBeUndefined();
  });
});
