/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { IUtilisateur } from "@api";
import { renderWithProviders } from "@/test";
import { TabIdentite } from "./TabIdentite";

const useGetItem = vi.fn();
const usePatch = vi.fn();
const mutate = vi.fn();
const useAuthMock = vi.fn();

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({ useGetItem, usePatch }),
}));
vi.mock("@/auth/AuthProvider", () => ({ useAuth: () => useAuthMock() }));
vi.mock("antd", async (importOriginal) => {
  const actual = await importOriginal<typeof import("antd")>();
  return {
    ...actual,
    App: {
      ...actual.App,
      useApp: () => ({ message: { success: vi.fn(() => Promise.resolve()), error: vi.fn() } }),
    },
  };
});

vi.mock("@controls/TabsContent/TabIdentite/IdentiteSection", () => ({
  IdentiteSection: () => <div data-testid="identite" />,
}));
vi.mock("@controls/TabsContent/TabIdentite/ScolariteSection", () => ({
  ScolariteSection: () => <div data-testid="scolarite" />,
}));
vi.mock("@controls/TabsContent/TabIdentite/SuiviSection", () => ({
  SuiviSection: () => <div data-testid="suivi" />,
}));
vi.mock("@controls/TabsContent/TabIdentite/InfosComplementairesSection", () => ({
  InfosComplementairesSection: () => <div data-testid="infos-complementaires" />,
}));
vi.mock("@controls/TabsContent/TabIdentite/CommentaireDemandeSection", () => ({
  CommentaireDemandeSection: (props: {
    commentaire?: string;
    setCommentaire: (v: string) => void;
  }) => (
    <input
      aria-label="commentaire"
      value={props.commentaire}
      onChange={(e) => props.setCommentaire(e.target.value)}
    />
  ),
}));

const utilisateur = { "@id": "/utilisateurs/jdoe" } as IUtilisateur;

interface Donnees {
  utilisateur?: IUtilisateur;
  demande?: { commentaire?: string };
  isGestionnaire?: boolean;
}

const configurer = ({ utilisateur: u, demande, isGestionnaire = false }: Donnees) => {
  useAuthMock.mockReturnValue({ user: { isGestionnaire } });
  useGetItem.mockImplementation(({ path }: { path: string }) =>
    path === "/utilisateurs/{uid}"
      ? { data: u, isFetching: false }
      : { data: demande, isFetching: false },
  );
};

describe("TabIdentite", () => {
  beforeEach(() => {
    usePatch.mockReturnValue({ mutate });
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it("n'affiche aucune section tant que l'utilisateur n'est pas chargé", () => {
    configurer({ utilisateur: undefined });
    renderWithProviders(<TabIdentite utilisateurId="jdoe" />);
    expect(screen.queryByTestId("identite")).not.toBeInTheDocument();
    expect(screen.queryByTestId("scolarite")).not.toBeInTheDocument();
  });

  it("affiche identité, scolarité et infos complémentaires pour un non-gestionnaire", () => {
    configurer({ utilisateur });
    renderWithProviders(<TabIdentite utilisateurId="jdoe" />);
    expect(screen.getByTestId("identite")).toBeInTheDocument();
    expect(screen.getByTestId("scolarite")).toBeInTheDocument();
    expect(screen.getByTestId("infos-complementaires")).toBeInTheDocument();
    expect(screen.queryByTestId("suivi")).not.toBeInTheDocument();
  });

  it("affiche la section suivi pour un gestionnaire", () => {
    configurer({ utilisateur, isGestionnaire: true });
    renderWithProviders(<TabIdentite utilisateurId="jdoe" />);
    expect(screen.getByTestId("suivi")).toBeInTheDocument();
  });

  it("n'affiche pas le commentaire sans demandeId", () => {
    configurer({ utilisateur, isGestionnaire: true });
    renderWithProviders(<TabIdentite utilisateurId="jdoe" />);
    expect(screen.queryByLabelText("commentaire")).not.toBeInTheDocument();
  });

  it("n'affiche pas le commentaire à un non-gestionnaire", () => {
    configurer({ utilisateur, demande: { commentaire: "Note" } });
    renderWithProviders(<TabIdentite utilisateurId="jdoe" demandeId="1" />);
    expect(screen.queryByLabelText("commentaire")).not.toBeInTheDocument();
  });

  it("initialise le commentaire depuis la demande pour un gestionnaire", () => {
    configurer({ utilisateur, demande: { commentaire: "Note" }, isGestionnaire: true });
    renderWithProviders(<TabIdentite utilisateurId="jdoe" demandeId="1" />);
    expect(screen.getByLabelText("commentaire")).toHaveValue("Note");
  });

  it("conserve la saisie du commentaire au re-rendu", async () => {
    configurer({ utilisateur, demande: { commentaire: "Note" }, isGestionnaire: true });
    renderWithProviders(<TabIdentite utilisateurId="jdoe" demandeId="1" />);
    await userEvent.type(screen.getByLabelText("commentaire"), "!");
    expect(screen.getByLabelText("commentaire")).toHaveValue("Note!");
  });

  it("utilise une chaîne vide si la demande n'a pas de commentaire", () => {
    configurer({ utilisateur, demande: {}, isGestionnaire: true });
    renderWithProviders(<TabIdentite utilisateurId="jdoe" demandeId="1" />);
    expect(screen.getByLabelText("commentaire")).toHaveValue("");
  });

  it("n'interroge la demande que si demandeId est fourni", () => {
    configurer({ utilisateur });
    renderWithProviders(<TabIdentite utilisateurId="jdoe" />);
    const appelDemande = useGetItem.mock.calls.find(([o]) => o.path === "/demandes/{id}");
    expect(appelDemande?.[0].enabled).toBe(false);
  });
});
