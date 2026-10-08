/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import BeneficiaireTableExport from "./BeneficiaireTableExport";
import { FILTRE_BENEFICIAIRE_DEFAULT } from "./BeneficiaireTable";

const mockCsvExportButton = vi.fn();
vi.mock("@controls/Table/Export/CsvExportButton", () => ({
  default: (props: unknown) => {
    mockCsvExportButton(props);
    return <button type="button">Exporter</button>;
  },
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetFullCollection: vi.fn(() => ({ data: { items: [] } })),
  }),
}));

describe("BeneficiaireTableExport", () => {
  it("génère les headers CSV par défaut si aucune colonne visible n'est fournie", () => {
    mockCsvExportButton.mockClear();
    render(<BeneficiaireTableExport filtreBeneficiaire={FILTRE_BENEFICIAIRE_DEFAULT} />);

    expect(screen.getByRole("button", { name: "Exporter" })).toBeInTheDocument();
    expect(mockCsvExportButton).toHaveBeenCalled();
    const lastProps = mockCsvExportButton.mock.calls[0]?.[0];
    const headerKeys = lastProps.headers.map((h: { key: string }) => h.key);
    expect(headerKeys).toContain("nom");
    expect(headerKeys).toContain("prenom");
    expect(headerKeys).toContain("email");
  });

  it("génère les headers CSV alignés sur colonnesVisibles dans le même ordre", () => {
    mockCsvExportButton.mockClear();
    render(
      <BeneficiaireTableExport
        filtreBeneficiaire={FILTRE_BENEFICIAIRE_DEFAULT}
        colonnesVisibles={["nom", "email", "composantes", "actions"]}
      />,
    );

    const lastProps = mockCsvExportButton.mock.calls[0]?.[0];
    const headerKeys = lastProps.headers.map((h: { key: string }) => h.key);
    // Doit avoir nom, prenom, email, composantes dans cet ordre
    expect(headerKeys).toEqual(["nom", "prenom", "email", "composantes"]);
    // Ne doit pas inclure 'actions'
    expect(headerKeys).not.toContain("actions");
  });

  it("prend en compte la réorganisation des colonnes dans le CSV", () => {
    mockCsvExportButton.mockClear();
    render(
      <BeneficiaireTableExport
        filtreBeneficiaire={FILTRE_BENEFICIAIRE_DEFAULT}
        colonnesVisibles={["nom", "tags", "numeroEtudiant"]}
      />,
    );

    const lastProps = mockCsvExportButton.mock.calls[0]?.[0];
    const headerKeys = lastProps.headers.map((h: { key: string }) => h.key);
    expect(headerKeys).toEqual(["nom", "prenom", "tags", "numeroEtudiant"]);
  });

  it("inclut les colonnes complémentaires dans les headers et mappe leurs valeurs dans getData", () => {
    mockCsvExportButton.mockClear();
    render(
      <BeneficiaireTableExport
        filtreBeneficiaire={FILTRE_BENEFICIAIRE_DEFAULT}
        colonnesVisibles={["nom", "Régime spécial", "actions"]}
      />,
    );

    const lastProps = mockCsvExportButton.mock.calls[0]?.[0];
    const headers = lastProps.headers;
    expect(headers).toEqual([
      { label: "Nom", key: "nom" },
      { label: "Prénom", key: "prenom" },
      { label: "Régime spécial", key: "Régime spécial" },
    ]);

    const fakeBeneficiaire = {
      "@id": "/utilisateurs/1",
      nom: "Dupont",
      prenom: "Jean",
      infosComplementaires: [
        { libelle: "Régime spécial", valeur: "Oui" },
        { libelle: "Autre info", valeur: "Non" },
      ],
    };
    const rowData = lastProps.getData([fakeBeneficiaire]);
    expect(rowData[0]["Régime spécial"]).toBe("Oui");
    expect(rowData[0]["Autre info"]).toBe("Non");
  });
});
