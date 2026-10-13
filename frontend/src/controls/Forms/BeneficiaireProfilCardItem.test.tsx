/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import React, { useEffect } from "react";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import dayjs from "dayjs";
import { axe } from "vitest-axe";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { drainAntdTimers, makeUtilisateur, renderWithProviders } from "@/test";
import BeneficiaireProfilCardItem from "./BeneficiaireProfilCardItem";

const { mockUseGetItem, montages } = vi.hoisted(() => ({
  mockUseGetItem: vi.fn(),
  montages: { nombre: 0 },
}));

const PROFILS = {
  data: {
    items: [{ "@id": "/profils/1", libelle: "Situation de handicap", avecTypologie: false }],
  },
  isFetching: false,
  isLoading: false,
};
const TYPOLOGIES = { data: { items: [] }, isFetching: false, isLoading: false };
const VALUE = "/utilisateurs/u1/profils/7";

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
vi.mock("@controls/Items/GestionnaireItem", () => ({
  GestionnaireItem: () => <span>Lesley GREEN</span>,
}));

function profil(debut: dayjs.Dayjs, fin: dayjs.Dayjs, avecAccompagnement = true) {
  return {
    "@id": VALUE,
    profil: "/profils/1",
    debut: debut.toISOString(),
    fin: fin.toISOString(),
    gestionnaire: "/utilisateurs/2",
    avecAccompagnement,
    typologies: [],
  };
}

const enCours = () => profil(dayjs().subtract(1, "month"), dayjs().add(1, "year"));
const termine = () => profil(dayjs().subtract(2, "year"), dayjs().subtract(1, "year"), false);

function charger(data: ReturnType<typeof profil>, isFetching = false) {
  mockUseGetItem.mockReturnValue({ data, isFetching, isLoading: false });
}

function carte(
  props: Partial<React.ComponentProps<typeof BeneficiaireProfilCardItem>> = {},
): React.ReactElement {
  return (
    <BeneficiaireProfilCardItem value={VALUE} utilisateur={makeUtilisateur() as never} {...props} />
  );
}

afterEach(async () => {
  await drainAntdTimers();
});

describe("BeneficiaireProfilCardItem", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    montages.nombre = 0;
    charger(enCours());
  });

  describe("affichage", () => {
    it("indique par écrit qu'un profil est en cours et met son libellé en avant", () => {
      renderWithProviders(carte());

      expect(screen.getByText("En cours")).toBeInTheDocument();
      expect(screen.queryByText("Terminé")).not.toBeInTheDocument();
      expect(screen.getByText("Situation de handicap").closest("strong")).toBeInTheDocument();
    });

    it("indique par écrit qu'un profil est terminé, sans mise en avant", () => {
      charger(termine());
      renderWithProviders(carte());

      expect(screen.getByText("Terminé")).toBeInTheDocument();
      expect(screen.queryByText("En cours")).not.toBeInTheDocument();
      expect(screen.getByText("Situation de handicap").closest("strong")).not.toBeInTheDocument();
    });

    it("affiche le gestionnaire et le type d'accompagnement", () => {
      const { rerender } = renderWithProviders(carte());

      expect(screen.getByText("Lesley GREEN")).toBeInTheDocument();
      expect(screen.getByText("Avec accompagnement")).toBeInTheDocument();

      charger(termine());
      rerender(carte());
      expect(screen.getByText("Sans accompagnement")).toBeInTheDocument();
    });

    it("affiche le contenu additionnel (ex. bouton de suppression) transmis par la liste", () => {
      renderWithProviders(carte({ extra: <button>Supprimer le profil</button> }));

      expect(screen.getByRole("button", { name: "Supprimer le profil" })).toBeInTheDocument();
    });

    it("ne présente pas de violation d'accessibilité", async () => {
      const { container } = renderWithProviders(carte());

      expect(await axe(container)).toHaveNoViolations();
    });
  });

  describe("édition", () => {
    it("sans valeur : formulaire d'ajout ; « Annuler » retire le champ", async () => {
      const onCancel = vi.fn();
      renderWithProviders(carte({ value: undefined, onCancel }));

      await userEvent.click(screen.getByRole("button", { name: "Annuler l'édition" }));
      expect(onCancel).toHaveBeenCalled();
    });

    it("avec valeur : « Annuler » de l'édition revient à la carte", async () => {
      const onCancel = vi.fn();
      renderWithProviders(carte({ onCancel }));

      await userEvent.click(screen.getByRole("button", { name: "Modifier le profil" }));
      await userEvent.click(screen.getByRole("button", { name: "Annuler l'édition" }));

      expect(onCancel).not.toHaveBeenCalled();
      expect(screen.getByText("Situation de handicap")).toBeInTheDocument();
    });

    it("un rafraîchissement pendant l'édition ne démonte pas le formulaire", async () => {
      const { rerender } = renderWithProviders(carte());
      await userEvent.click(screen.getByRole("button", { name: "Modifier le profil" }));
      expect(montages.nombre).toBe(1);

      charger(enCours(), true);
      rerender(carte());

      expect(screen.getByRole("button", { name: "Annuler l'édition" })).toBeInTheDocument();
      expect(montages.nombre).toBe(1);
    });

    it("editable={false} : pas de bouton de modification", () => {
      renderWithProviders(carte({ editable: false }));

      expect(screen.queryByRole("button", { name: "Modifier le profil" })).not.toBeInTheDocument();
    });
  });
});
