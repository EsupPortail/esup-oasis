/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, expect, it, vi } from "vitest";
import { renderWithProviders } from "@/test";
import { IProfil } from "@api";
import { ProfilsEdition } from "./ProfilsEdition";

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    usePost: vi.fn(() => ({ mutate: vi.fn() })),
    usePatch: vi.fn(() => ({ mutate: vi.fn() })),
  }),
}));

const OPTION = /Avis médical requis pour éditer/;

function profil(avecTypologie: boolean): IProfil {
  return {
    "@id": "/profils/1",
    libelle: "Handicap permanent",
    actif: true,
    avecTypologie,
    avisMedicalRequis: false,
  } as unknown as IProfil;
}

describe("ProfilsEdition", () => {
  it("propose l'exigence de l'avis médical pour un profil de handicap", async () => {
    renderWithProviders(<ProfilsEdition editedItem={profil(true)} setEditedItem={vi.fn()} />);

    expect(await screen.findByLabelText(OPTION)).toBeInTheDocument();
  });

  it("ne la propose pas pour un profil sans typologie de handicap", async () => {
    renderWithProviders(<ProfilsEdition editedItem={profil(false)} setEditedItem={vi.fn()} />);

    await screen.findByLabelText("Typologie de handicap à associer");
    expect(screen.queryByLabelText(OPTION)).not.toBeInTheDocument();
  });

  it("la fait apparaître quand le profil devient un profil de handicap", async () => {
    renderWithProviders(<ProfilsEdition editedItem={profil(false)} setEditedItem={vi.fn()} />);

    await userEvent.click(await screen.findByLabelText("Typologie de handicap à associer"));

    expect(await screen.findByLabelText(OPTION)).toBeInTheDocument();
  });
});
