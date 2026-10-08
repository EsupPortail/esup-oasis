/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { Form } from "antd";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { IUtilisateur } from "@api";
import { RoleValues } from "@lib";
import { renderWithProviders } from "@/test";
import { SuiviSection } from "./SuiviSection";

const messageError = vi.fn(() => Promise.resolve());
vi.mock("antd", async (importOriginal) => {
  const actual = await importOriginal<typeof import("antd")>();
  return {
    ...actual,
    App: {
      ...actual.App,
      useApp: () => ({ message: { error: messageError, success: vi.fn() } }),
    },
  };
});

vi.mock("@controls/TabsContent/TabProfils", () => ({
  TabProfils: () => <div data-testid="tab-profils" />,
}));

const Harness: React.FC<{
  utilisateur: Partial<IUtilisateur>;
  mutateUtilisateur: () => void;
  isFetching?: boolean;
}> = ({ utilisateur, mutateUtilisateur, isFetching = false }) => {
  const [form] = Form.useForm();
  return (
    <SuiviSection
      utilisateur={utilisateur as IUtilisateur}
      isFetching={isFetching}
      mutateUtilisateur={mutateUtilisateur}
      form={form}
    />
  );
};

const beneficiaire = {
  "@id": "/utilisateurs/jdoe",
  roles: [RoleValues.ROLE_BENEFICIAIRE],
  profils: [],
} as Partial<IUtilisateur>;

const rendre = (utilisateur: Partial<IUtilisateur>, isFetching = false) => {
  const mutateUtilisateur = vi.fn();
  renderWithProviders(
    <Harness
      utilisateur={utilisateur}
      mutateUtilisateur={mutateUtilisateur}
      isFetching={isFetching}
    />,
  );
  return { mutateUtilisateur };
};

const saisirNumero = async (valeur: string) => {
  await userEvent.click(screen.getByRole("button", { name: "Edit" }));
  const champ = screen.getByRole("textbox");
  await userEvent.clear(champ);
  if (valeur) await userEvent.type(champ, valeur);
  await userEvent.tab();
};

describe("SuiviSection", () => {
  beforeEach(() => {
    messageError.mockClear();
  });

  it("affiche le numéro d'anonymat uniquement pour un bénéficiaire", () => {
    rendre({ ...beneficiaire, roles: [] });
    expect(screen.queryByText("Numéro d'anonymat")).not.toBeInTheDocument();
  });

  it("affiche le champ numéro d'anonymat pour un bénéficiaire", () => {
    rendre(beneficiaire);
    expect(screen.getByText("Numéro d'anonymat")).toBeInTheDocument();
  });

  it("n'est pas éditable si le numéro existe déjà et propose la copie", () => {
    rendre({ ...beneficiaire, numeroAnonyme: 12345678 });
    expect(screen.getByText("12345678")).toBeInTheDocument();
    expect(screen.queryByRole("button", { name: "Edit" })).not.toBeInTheDocument();
    expect(screen.getByRole("button", { name: /copy|copier/i })).toBeInTheDocument();
  });

  it("refuse un numéro qui n'a pas 8 chiffres", async () => {
    const { mutateUtilisateur } = rendre(beneficiaire);
    await saisirNumero("123");
    await waitFor(() =>
      expect(messageError).toHaveBeenCalledWith(
        "Le numéro d'anonymat doit être composé de 8 chiffres",
      ),
    );
    expect(mutateUtilisateur).not.toHaveBeenCalled();
  });

  it("ignore une saisie vide sans erreur ni requête", async () => {
    const { mutateUtilisateur } = rendre(beneficiaire);
    await saisirNumero("");
    expect(mutateUtilisateur).not.toHaveBeenCalled();
    expect(messageError).not.toHaveBeenCalled();
  });

  it("enregistre un numéro valide", async () => {
    const { mutateUtilisateur } = rendre(beneficiaire);
    await saisirNumero("12345678");
    await waitFor(() =>
      expect(mutateUtilisateur).toHaveBeenCalledWith({
        data: { numeroAnonyme: 12345678 },
        "@id": "/utilisateurs/jdoe",
      }),
    );
  });

  it("indique l'absence de profil", () => {
    rendre(beneficiaire);
    expect(screen.getByText("Aucun profil actuellement")).toBeInTheDocument();
    expect(screen.getByTestId("tab-profils")).toBeInTheDocument();
  });

  it("n'indique pas l'absence de profil quand il y en a", () => {
    rendre({
      ...beneficiaire,
      profils: [{ "@id": "/profils/1" }],
    } as unknown as Partial<IUtilisateur>);
    expect(screen.queryByText("Aucun profil actuellement")).not.toBeInTheDocument();
  });

  it("affiche un skeleton pendant le chargement", () => {
    rendre(beneficiaire, true);
    expect(screen.queryByTestId("tab-profils")).not.toBeInTheDocument();
  });
});
