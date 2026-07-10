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
import { describe, it, expect, vi } from "vitest";
import { renderWithProviders } from "@/test";
import MenuPreview from "@controls/Admin/Menus/preview/MenuPreview";
import { MENUS_ADMIN_CONFIG } from "@controls/Admin/Menus/menusAdminConfig";

// Ant Design ne rend les sous-menus (popup) que sur interaction, absente en jsdom :
// on aplatit récursivement pour exposer chaque libellé, y compris les sous-items.
vi.mock("antd", async (importOriginal) => {
  const actual = await importOriginal<typeof import("antd")>();
  interface FlatItem {
    key?: string;
    label?: React.ReactNode;
    children?: FlatItem[];
  }
  const FlatMenu = ({ items }: { items?: Array<FlatItem | null> }) => {
    const renderItems = (list: Array<FlatItem | null>): React.ReactNode =>
      (list ?? []).map((entry, i) =>
        entry ? (
          <li key={entry.key ?? i} data-menu-key={entry.key ?? ""}>
            {entry.label}
            {entry.children && <ul>{renderItems(entry.children)}</ul>}
          </li>
        ) : null,
      );
    return <ul aria-label="Menu">{renderItems(items ?? [])}</ul>;
  };
  return { ...actual, Menu: FlatMenu };
});

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetItem: () => ({ data: undefined, isFetching: false }),
    useGetCollection: () => ({ data: undefined, isFetching: false }),
    useGetFullCollection: () => ({ data: undefined, isFetching: false }),
  }),
}));

function ongletParKey(key: string) {
  const onglet = MENUS_ADMIN_CONFIG.find((o) => o.key === key);
  if (!onglet) throw new Error(`Onglet ${key} introuvable dans MENUS_ADMIN_CONFIG`);
  return onglet;
}

// jsdom normalise les couleurs hex lues via `.style.background` au format rgb().
function hexEnRgb(hex: string): string {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  return `rgb(${r}, ${g}, ${b})`;
}

describe("MenuPreview", () => {
  it("onglet Gestionnaire : affiche les items conditionnels révélés par l'utilisateur simulé", () => {
    renderWithProviders(<MenuPreview onglet={ongletParKey("gestionnaire")} labels={{}} />);

    expect(screen.getByText("Demandeurs")).toBeInTheDocument();
    // "Bénéficiaires" apparaît deux fois (item racine + premier sous-item).
    expect(screen.getAllByText("Bénéficiaires").length).toBeGreaterThanOrEqual(2);
    expect(screen.getByText("Intervenants")).toBeInTheDocument();
    // Sous-item conditionné à isGestionnaire, révélé par le mock gestionnaire.
    expect(screen.getByText("Validation des interventions des renforts")).toBeInTheDocument();
    // Réservé au renfort : absent pour un gestionnaire qui n'a pas ce rôle.
    expect(screen.queryByText("Vos interventions (renfort)")).not.toBeInTheDocument();
  });

  it("onglet Renfort : affiche l'item exclusif au renfort, pas ceux réservés au gestionnaire", () => {
    renderWithProviders(<MenuPreview onglet={ongletParKey("renfort")} labels={{}} />);

    expect(screen.getByText("Vos interventions (renfort)")).toBeInTheDocument();
    expect(screen.queryByText("Validation des interventions des renforts")).not.toBeInTheDocument();
  });

  it("restitue une surcharge de labels à la place du libellé par défaut", () => {
    renderWithProviders(
      <MenuPreview
        onglet={ongletParKey("gestionnaire")}
        labels={{ MENU_PLANNING_PLANIF: "Mon planning perso" }}
      />,
    );

    expect(screen.getByText("Mon planning perso")).toBeInTheDocument();
    expect(screen.queryByText("Planning")).not.toBeInTheDocument();
  });

  it("onglet Commun : affiche les items transverses (thème, accessibilité, utilisateur)", () => {
    renderWithProviders(<MenuPreview onglet={ongletParKey("commun")} labels={{}} />);

    expect(screen.getByText("Déconnexion")).toBeInTheDocument();
    expect(screen.getByText("Administration")).toBeInTheDocument();
    // L'usurpation d'identité est une capacité admin : pas d'"impersonate" actif ici.
    expect(screen.queryByText("Récupérer mon identité")).not.toBeInTheDocument();
  });

  it("« Récupérer mon identité » n'apparaît que dans l'aperçu de l'onglet Administrateur", () => {
    const { unmount: fermerAdministrateur } = renderWithProviders(
      <MenuPreview onglet={ongletParKey("administrateur")} labels={{}} />,
    );
    expect(screen.getByText("Récupérer mon identité")).toBeInTheDocument();
    fermerAdministrateur();

    [
      "gestionnaire",
      "renfort",
      "demandeur",
      "beneficiaire",
      "intervenant",
      "commission",
      "referent",
      "commun",
    ].forEach((key) => {
      const { unmount } = renderWithProviders(
        <MenuPreview onglet={ongletParKey(key)} labels={{}} />,
      );
      expect(screen.queryByText("Récupérer mon identité")).not.toBeInTheDocument();
      unmount();
    });
  });

  it("onglet Demandeur : n'affiche pas les items du gestionnaire", () => {
    renderWithProviders(<MenuPreview onglet={ongletParKey("demandeur")} labels={{}} />);

    expect(screen.getByText("Demandes")).toBeInTheDocument();
    expect(screen.queryByText("Intervenants")).not.toBeInTheDocument();
  });

  it("onglet Administrateur : affiche Administration (l'admin implique isGestionnaire en production)", () => {
    renderWithProviders(<MenuPreview onglet={ongletParKey("administrateur")} labels={{}} />);

    expect(screen.getByText("Administration")).toBeInTheDocument();
    expect(screen.getByText("Déconnexion")).toBeInTheDocument();
    // isAdmin implique isGestionnaire dans Utilisateur.ts : le planning planificateur apparaît.
    expect(screen.getByText("Demandeurs")).toBeInTheDocument();
  });

  it("onglet de rôle : inclut aussi les items communs, en respectant les conditions du rôle", () => {
    renderWithProviders(<MenuPreview onglet={ongletParKey("beneficiaire")} labels={{}} />);

    expect(screen.getByText("Planning")).toBeInTheDocument();
    // Items communs toujours présents.
    expect(screen.getByText("Déconnexion")).toBeInTheDocument();
    // "Mon profil" visible car le mock Bénéficiaire a isBeneficiaire.
    expect(screen.getByText("Mon profil")).toBeInTheDocument();
    // "Administration" réservé aux admins : absent pour un simple bénéficiaire.
    expect(screen.queryByText("Administration")).not.toBeInTheDocument();
  });

  it("colore chaque item avec l'accent de l'onglet où il est réellement modifiable", () => {
    const { container } = renderWithProviders(
      <MenuPreview onglet={ongletParKey("administrateur")} labels={{}} />,
    );

    // "Demandeurs" appartient à l'onglet Gestionnaire : sa pastille doit reprendre sa couleur,
    // pas celle de l'onglet Administrateur affiché ici.
    const demandeurs = screen.getByText("Demandeurs").closest(".menu-preview-item");
    const pointDemandeurs = demandeurs?.querySelector<HTMLElement>(".menus-admin-tab-dot");
    expect(pointDemandeurs?.style.background).toBe(hexEnRgb(ongletParKey("gestionnaire").accent));

    // "Administration" appartient bien à l'onglet Administrateur affiché.
    const administration = screen.getByText("Administration").closest(".menu-preview-item");
    const pointAdministration = administration?.querySelector<HTMLElement>(".menus-admin-tab-dot");
    expect(pointAdministration?.style.background).toBe(
      hexEnRgb(ongletParKey("administrateur").accent),
    );

    expect(container.querySelectorAll(".menus-admin-tab-dot").length).toBeGreaterThan(0);
  });
});
