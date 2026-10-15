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
import { IAmenagement, IBeneficiaire, IComposante, IDemande, IInscription } from "@api";
import { PorteeExportInscriptions } from "@lib/inscription";
import { getInscriptionsHeaders } from "./Export/inscriptionsExportHeaders";
import { getBeneficiairesData } from "./BeneficiaireTableExport";
import { getDemandesExportData } from "./DemandeTableExport";
import { getAmenagementsData } from "./AmenagementTableExport";
import { formatAmenagementsBeneficiaireData } from "./AmenagementsBeneficiaireTableExport";

const composantes = [
  { "@id": "/composantes/1", libelle: "Sciences" },
  { "@id": "/composantes/2", libelle: "Droit" },
] as IComposante[];

const inscriptions = [
  {
    "@id": "/i/passee",
    debut: "2000-09-01T00:00:00+00:00",
    fin: "2001-08-31T00:00:00+00:00",
    principale: false,
    formation: { composante: "/composantes/1", libelle: "Ancienne licence" },
  },
  {
    "@id": "/i/secondaire",
    debut: "2000-09-01T00:00:00+00:00",
    fin: "2999-08-31T00:00:00+00:00",
    principale: false,
    formation: { composante: "/composantes/2", libelle: "Licence droit" },
  },
  {
    "@id": "/i/principale",
    debut: "2000-09-01T00:00:00+00:00",
    fin: "2999-08-31T00:00:00+00:00",
    principale: true,
    formation: { composante: "/composantes/1", libelle: "Master", codeExterne: "M1" },
  },
] as unknown as IInscription[];

/** Colonnes d'inscriptions attendues selon la portée choisie. */
const attendu = (portee: PorteeExportInscriptions) => ({
  composante: "Sciences",
  formation: "[M1] Master",
  enCours: portee === "principale" ? "" : "Droit | Licence droit | 2000-2001",
  passees: portee === "toutes" ? "Sciences | Ancienne licence | 2000-2001" : "",
});

const exports: Record<string, (portee: PorteeExportInscriptions) => Record<string, unknown>> = {
  beneficiaires: (portee) =>
    getBeneficiairesData(
      [{ "@id": "/b/1", inscriptions } as unknown as IBeneficiaire],
      composantes,
      [],
      [],
      portee,
    )[0],
  demandes: (portee) =>
    getDemandesExportData(
      [{ "@id": "/d/1", demandeur: { inscriptions } } as unknown as IDemande],
      composantes,
      [],
      [],
      [],
      portee,
    )[0],
  "aménagements (par aménagement)": (portee) =>
    getAmenagementsData(
      [{ "@id": "/a/1", beneficiaire: { inscriptions } } as unknown as IAmenagement],
      [],
      [],
      [],
      [],
      [],
      composantes,
      portee,
    )[0],
  "aménagements (par bénéficiaire)": (portee) =>
    formatAmenagementsBeneficiaireData(
      [{ key: "/a/utilisateurs/1", inscriptions, tags: [] }],
      [],
      [],
      composantes,
      portee,
    )[0],
};

describe.each(Object.entries(exports))("export %s — inscriptions", (_nom, exporter) => {
  it.each(["en_cours", "toutes"] as const)(
    "portée %s : principale + autres inscriptions en cours / passées",
    (portee) => {
      const ligne = exporter(portee);

      expect(ligne.composante).toBe(attendu(portee).composante);
      expect(ligne.formation).toBe(attendu(portee).formation);
      expect(ligne.autresInscriptionsEnCours).toBe(attendu(portee).enCours);
      expect(ligne.autresInscriptionsPassees).toBe(attendu(portee).passees);
    },
  );

  it("portée principale : aucune autre inscription", () => {
    const ligne = exporter("principale");

    expect(ligne.formation).toBe("[M1] Master");
    expect(ligne.autresInscriptionsEnCours).toBe("");
    expect(ligne.autresInscriptionsPassees).toBe("");
  });
});

describe("getInscriptionsHeaders", () => {
  const principale = [
    { label: "Composante", key: "composante" },
    { label: "Formation", key: "formation" },
  ];
  const enCours = { label: "Autres inscriptions en cours", key: "autresInscriptionsEnCours" };
  const passees = { label: "Autres inscriptions passées", key: "autresInscriptionsPassees" };

  it("portée principale : ni autres inscriptions en cours, ni passées", () => {
    expect(getInscriptionsHeaders("principale")).toEqual(principale);
  });

  it("portée en_cours : ajoute « Autres inscriptions en cours »", () => {
    expect(getInscriptionsHeaders("en_cours")).toEqual([...principale, enCours]);
  });

  it("portée toutes : ajoute les colonnes en cours et passées", () => {
    expect(getInscriptionsHeaders("toutes")).toEqual([...principale, enCours, passees]);
  });
});
