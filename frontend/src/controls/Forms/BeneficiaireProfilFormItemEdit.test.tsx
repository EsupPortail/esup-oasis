/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { fireEvent, screen } from "@testing-library/react";
import { App } from "antd";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { drainAntdTimers, makeUtilisateur, renderWithProviders } from "@/test";
import { IBeneficiaireProfil } from "@api";
import { BeneficiaireProfilFormItemEdit } from "./BeneficiaireProfilFormItemEdit";

interface OptionsMutation {
  onSuccess?: () => void;
  onError?: () => void;
}

const { mutations } = vi.hoisted(() => ({
  mutations: {} as Record<
    "post" | "patch",
    { mutate: ReturnType<typeof vi.fn>; options: OptionsMutation }
  >,
}));

function mutation(nom: "post" | "patch") {
  return (options: OptionsMutation) => {
    mutations[nom] ??= { mutate: vi.fn(), options };
    mutations[nom].options = options;
    return { mutate: mutations[nom].mutate, isPending: false };
  };
}

const COLLECTIONS: Record<string, { data: { items: unknown[] }; isFetching: boolean }> = {
  "/profils": {
    data: {
      items: [
        { "@id": "/profils/1", libelle: "Handicap", avecTypologie: true, actif: true },
        { "@id": "/profils/2", libelle: "Sportif", avecTypologie: false, actif: true },
        { "@id": "/profils/3", libelle: "Ancien profil", avecTypologie: false, actif: false },
      ],
    },
    isFetching: false,
  },
  "/typologies": {
    data: { items: [{ "@id": "/typologies/1", libelle: "Moteur" }] },
    isFetching: false,
  },
  "/roles/{roleId}/utilisateurs": {
    data: { items: [{ "@id": "/utilisateurs/g1", nom: "Durand", prenom: "Anne" }] },
    isFetching: false,
  },
};

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetFullCollection: (options: { path: string }) => COLLECTIONS[options.path],
    usePost: mutation("post"),
    usePatch: mutation("patch"),
  }),
}));
vi.mock("@/auth/AuthProvider", () => ({
  useAuth: () => ({ user: { "@id": "/utilisateurs/g1" } }),
}));

const PROFIL_HANDICAP = {
  "@id": "/utilisateurs/u1/profils/7",
  profil: "/profils/1",
  typologies: ["/typologies/1"],
  debut: "2025-09-01T00:00:00+00:00",
  fin: null,
  gestionnaire: "/utilisateurs/g1",
  avecAccompagnement: false,
} as unknown as IBeneficiaireProfil;

function rendre(profilBeneficiaire?: IBeneficiaireProfil) {
  renderWithProviders(
    <App>
      <BeneficiaireProfilFormItemEdit
        utilisateur={makeUtilisateur({ "@id": "/utilisateurs/u1" }) as never}
        profilBeneficiaire={profilBeneficiaire}
      />
    </App>,
  );
}

function ouvrir(index: number) {
  fireEvent.mouseDown(screen.getAllByRole("combobox")[index]);
}

function choisir(index: number, libelle: string) {
  ouvrir(index);
  fireEvent.click(
    document.querySelector(`.ant-select-item-option[title="${libelle}"]`) as HTMLElement,
  );
}

function saisirDebut(valeur: string) {
  const input = screen.getByPlaceholderText("Début");
  fireEvent.mouseDown(input);
  fireEvent.change(input, { target: { value: valeur } });
  fireEvent.keyDown(input, { key: "Enter", code: "Enter" });
}

function boutonEnregistrer() {
  return screen.getByRole("button", { name: /Enregistrer le profil/ });
}

afterEach(async () => {
  await drainAntdTimers();
});

describe("BeneficiaireProfilFormItemEdit", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("champ obligatoire manquant : message d'erreur, aucune mutation", async () => {
    rendre();
    fireEvent.click(boutonEnregistrer());

    expect(
      await screen.findByText("Veuillez renseigner tous les champs obligatoires"),
    ).toBeInTheDocument();
    expect(mutations.post.mutate).not.toHaveBeenCalled();
  });

  it("création : POST avec le gestionnaire connecté et l'accompagnement par défaut", () => {
    rendre();
    choisir(0, "Sportif");
    saisirDebut("2025-09-01");
    fireEvent.click(boutonEnregistrer());

    expect(mutations.post.mutate).toHaveBeenCalledWith({
      data: {
        profil: "/profils/2",
        debut: "2025-09-01T00:00:00.000Z",
        fin: null,
        gestionnaire: "/utilisateurs/g1",
        typologies: [],
        avecAccompagnement: true,
      },
    });
  });

  it("édition : PATCH sur l'@id du profil, accompagnement initial conservé", () => {
    rendre(PROFIL_HANDICAP);
    fireEvent.click(boutonEnregistrer());

    expect(mutations.patch.mutate).toHaveBeenCalledWith({
      "@id": "/utilisateurs/u1/profils/7",
      data: expect.objectContaining({
        typologies: ["/typologies/1"],
        avecAccompagnement: false,
      }),
    });
  });

  it("erreur de l'API : le bouton n'est plus en chargement", () => {
    rendre(PROFIL_HANDICAP);
    fireEvent.click(boutonEnregistrer());
    mutations.patch.options.onError?.();

    expect(boutonEnregistrer()).not.toHaveClass("ant-btn-loading");
  });

  it("passage à un profil sans typologie : les typologies ne sont pas envoyées", () => {
    rendre(PROFIL_HANDICAP);
    choisir(0, "Sportif");
    fireEvent.click(boutonEnregistrer());

    expect(mutations.patch.mutate).toHaveBeenCalledWith({
      "@id": "/utilisateurs/u1/profils/7",
      data: expect.objectContaining({ profil: "/profils/2", typologies: [] }),
    });
  });

  it("profil avec typologie sans typologie choisie : aucune mutation", async () => {
    rendre({ ...PROFIL_HANDICAP, typologies: [] });
    fireEvent.click(boutonEnregistrer());

    expect(
      await screen.findByText("Veuillez sélectionner au moins une typologie de handicap"),
    ).toBeInTheDocument();
    expect(mutations.patch.mutate).not.toHaveBeenCalled();
  });

  it("seuls les profils actifs sont proposés, plus le profil déjà attribué", () => {
    const { unmount } = renderWithProviders(
      <App>
        <BeneficiaireProfilFormItemEdit utilisateur={makeUtilisateur() as never} />
      </App>,
    );
    ouvrir(0);
    expect(document.querySelector('.ant-select-item-option[title="Ancien profil"]')).toBeNull();
    expect(document.querySelector('.ant-select-item-option[title="Sportif"]')).not.toBeNull();
    unmount();

    rendre({ ...PROFIL_HANDICAP, profil: "/profils/3", typologies: [] });
    expect(screen.getByTitle("Ancien profil")).toBeInTheDocument();
  });
});
