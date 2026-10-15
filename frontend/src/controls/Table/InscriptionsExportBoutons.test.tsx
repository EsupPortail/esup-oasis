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
import { beforeEach, describe, expect, it, vi } from "vitest";
import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { FiltreBeneficiaire } from "@controls/Table/BeneficiaireTable";
import { FiltreDemande } from "@controls/Table/DemandeTable";
import { FiltreAmenagement } from "@controls/Table/AmenagementTableLayout";
import BeneficiaireTableExport from "./BeneficiaireTableExport";
import DemandeTableExport from "./DemandeTableExport";
import AmenagementTableExport from "./AmenagementTableExport";
import AmenagementsBeneficiaireTableExport from "./AmenagementsBeneficiaireTableExport";

/** En-têtes (clés) du dernier CSV généré. */
let clesCsv: string[] = [];
const mockUser = { isGestionnaire: true, isPlanificateur: true, isReferentComposante: false };
const mockSetPreference = vi.fn();

vi.mock("react-csv", async () => {
  const { useImperativeHandle } = await import("react");
  return {
    CSVLink: ({
      ref,
      onClick,
      headers,
      children,
    }: {
      ref: React.Ref<unknown>;
      onClick: () => void;
      headers: { key: string }[];
      children: React.ReactNode;
    }) => {
      clesCsv = headers.map((h) => h.key);
      useImperativeHandle(ref, () => ({ link: { click: onClick } }));
      return <span>{children}</span>;
    },
  };
});

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetFullCollection: () => ({
      data: { items: [] },
      isError: false,
      isSuccess: true,
      isLoadingPage1: false,
      fetchedCount: 0,
      totalItems: 0,
    }),
  }),
}));
vi.mock("@/auth/AuthProvider", () => ({ useAuth: () => ({ user: mockUser }) }));
vi.mock("@context/utilisateurPreferences/UtilisateurPreferencesProvider", () => ({
  usePreferences: () => ({ getPreference: () => undefined, setPreference: mockSetPreference }),
}));

const filtreAmenagement = { page: 1, itemsPerPage: 10 } as FiltreAmenagement;

const exports: Record<string, () => React.ReactElement> = {
  "/beneficiaires": () => (
    <BeneficiaireTableExport
      filtreBeneficiaire={{ page: 1, itemsPerPage: 10 } as FiltreBeneficiaire}
    />
  ),
  "/demandeurs": () => (
    <DemandeTableExport filtreDemande={{ page: 1, itemsPerPage: 10 } as FiltreDemande} />
  ),
  "/amenagements?mode=amenagement": () => (
    <AmenagementTableExport filtreAmenagement={filtreAmenagement} />
  ),
  "/amenagements?mode=beneficiaire": () => (
    <AmenagementsBeneficiaireTableExport
      filtreAmenagement={filtreAmenagement}
      typesAmenagements={[]}
    />
  ),
};

const exporter = () => userEvent.click(screen.getByRole("button", { name: /Exporter$/ }));
const choisir = (libelle: RegExp) => userEvent.click(screen.getByRole("radio", { name: libelle }));
const validerModale = () =>
  userEvent.click(within(screen.getByRole("dialog")).getByRole("button", { name: "Exporter" }));

describe.each(Object.entries(exports))("export %s — choix des inscriptions", (_page, composant) => {
  beforeEach(() => {
    clesCsv = [];
    mockSetPreference.mockClear();
    Object.assign(mockUser, {
      isGestionnaire: true,
      isPlanificateur: true,
      isReferentComposante: false,
    });
  });

  it("demande les inscriptions à exporter avant de lancer l'export", async () => {
    render(composant());

    await exporter();

    expect(await screen.findByRole("dialog")).toBeInTheDocument();
    expect(clesCsv).toEqual([]);
  });

  it("« toutes » : colonnes des inscriptions en cours et passées, choix mémorisé", async () => {
    render(composant());

    await exporter();
    await choisir(/^Toutes les inscriptions/);
    await validerModale();

    await waitFor(() => expect(clesCsv).toContain("autresInscriptionsPassees"));
    expect(clesCsv).toEqual(
      expect.arrayContaining(["composante", "formation", "autresInscriptionsEnCours"]),
    );
    expect(mockSetPreference).toHaveBeenCalledWith("exportInscriptions", "toutes");
  });

  it("« principale » : ni autres inscriptions en cours, ni passées", async () => {
    render(composant());

    await exporter();
    await choisir(/^Inscription principale uniquement/);
    await validerModale();

    await waitFor(() => expect(clesCsv).toContain("formation"));
    expect(clesCsv).not.toContain("autresInscriptionsEnCours");
    expect(clesCsv).not.toContain("autresInscriptionsPassees");
  });

  it("référent de composante : pas de modale, inscriptions en cours uniquement", async () => {
    Object.assign(mockUser, {
      isGestionnaire: false,
      isPlanificateur: false,
      isReferentComposante: true,
    });
    render(composant());

    await exporter();

    await waitFor(() => expect(clesCsv).toContain("autresInscriptionsEnCours"));
    expect(clesCsv).not.toContain("autresInscriptionsPassees");
    expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    expect(mockSetPreference).not.toHaveBeenCalled();
  });
});
