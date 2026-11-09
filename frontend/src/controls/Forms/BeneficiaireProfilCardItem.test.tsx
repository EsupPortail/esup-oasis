/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useEffect } from "react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { drainAntdTimers, makeUtilisateur, renderWithProviders } from "@/test";
import BeneficiaireProfilCardItem from "./BeneficiaireProfilCardItem";

const { mockUseGetItem, montages } = vi.hoisted(() => ({
  mockUseGetItem: vi.fn(),
  montages: { nombre: 0 },
}));

const PROFILS = {
  data: { items: [{ "@id": "/profils/1", libelle: "Sportif", avecTypologie: false }] },
  isFetching: false,
  isLoading: false,
};
const TYPOLOGIES = { data: { items: [] }, isFetching: false, isLoading: false };
const PROFIL_BENEFICIAIRE = {
  "@id": "/utilisateurs/u1/profils/7",
  profil: "/profils/1",
  debut: "2025-09-01T00:00:00+00:00",
  fin: "2026-08-31T00:00:00+00:00",
};

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetFullCollection: (options: { path: string }) =>
      options.path === "/profils" ? PROFILS : TYPOLOGIES,
    useGetItem: mockUseGetItem,
  }),
}));
vi.mock("@controls/Forms/BeneficiaireProfilFormItemEdit", () => ({
  BeneficiaireProfilFormItemEdit: (props: { onCancel: () => void }) => {
    useEffect(() => {
      montages.nombre += 1;
    }, []);
    return (
      <button type="button" onClick={props.onCancel}>
        Annuler l'édition
      </button>
    );
  },
}));
vi.mock("@controls/Items/GestionnaireItem", () => ({ GestionnaireItem: () => null }));
vi.mock("@controls/Avatars/AccompagnementAvatar", () => ({ AccompagnementAvatar: () => null }));

function etatItem(isFetching: boolean) {
  return { data: PROFIL_BENEFICIAIRE, isFetching, isLoading: false };
}

function carte(props: { value?: string; editable?: boolean; onCancel?: () => void }) {
  return <BeneficiaireProfilCardItem utilisateur={makeUtilisateur() as never} {...props} />;
}

afterEach(async () => {
  await drainAntdTimers();
});

describe("BeneficiaireProfilCardItem", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    montages.nombre = 0;
    mockUseGetItem.mockReturnValue(etatItem(false));
  });

  it("sans valeur : formulaire d'ajout ; « Annuler » retire le champ", async () => {
    const onCancel = vi.fn();
    renderWithProviders(carte({ onCancel }));

    await userEvent.click(screen.getByRole("button", { name: "Annuler l'édition" }));
    expect(onCancel).toHaveBeenCalled();
  });

  it("avec valeur : carte du profil ; « Annuler » de l'édition revient à la carte", async () => {
    const onCancel = vi.fn();
    renderWithProviders(carte({ value: PROFIL_BENEFICIAIRE["@id"], onCancel }));
    expect(screen.getByText("Sportif")).toBeInTheDocument();
    expect(screen.getByText(/^01 .* 2025 au 31 .* 2026$/)).toBeInTheDocument();

    await userEvent.click(screen.getByRole("button", { name: "edit" }));
    await userEvent.click(screen.getByRole("button", { name: "Annuler l'édition" }));

    expect(onCancel).not.toHaveBeenCalled();
    expect(screen.getByText("Sportif")).toBeInTheDocument();
  });

  it("un rafraîchissement pendant l'édition ne démonte pas le formulaire", async () => {
    const { rerender } = renderWithProviders(carte({ value: PROFIL_BENEFICIAIRE["@id"] }));
    await userEvent.click(screen.getByRole("button", { name: "edit" }));
    expect(montages.nombre).toBe(1);

    mockUseGetItem.mockReturnValue(etatItem(true));
    rerender(carte({ value: PROFIL_BENEFICIAIRE["@id"] }));

    expect(screen.getByRole("button", { name: "Annuler l'édition" })).toBeInTheDocument();
    expect(montages.nombre).toBe(1);
  });

  it("editable={false} : pas de bouton d'édition", () => {
    renderWithProviders(carte({ value: PROFIL_BENEFICIAIRE["@id"], editable: false }));
    expect(screen.queryByRole("button", { name: "edit" })).toBeNull();
  });
});
