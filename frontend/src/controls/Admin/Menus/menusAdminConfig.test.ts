/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { describe, expect, it, vi } from "vitest";
import { MENUS_ADMIN_CONFIG } from "@controls/Admin/Menus/menusAdminConfig";
import { MenuLabelNode } from "@controls/Admin/Menus/menusAdminConfig.types";
import { makeTestEnv } from "@/test/env";

// Clés créées par la migration backend/migrations/Version20260710120000.php
const CLES_MENU_ATTENDUES = [
  "MENU_DEMANDEURS",
  "MENU_BENEFICIAIRES",
  "MENU_BENEFICIAIRES_LISTE",
  "MENU_BENEFICIAIRES_AMENAGEMENT_PAR_BENEF",
  "MENU_BENEFICIAIRES_AMENAGEMENT",
  "MENU_INTERVENANTS",
  "MENU_PLANNING_PLANIF",
  "MENU_PLANNING_INTERVENTIONS",
  "MENU_PLANNING_INTERVENTIONS_FORFAIT",
  "MENU_PLANNING_VALIDATION_RENFORTS",
  "MENU_PLANNING_MES_INTERVENTIONS",
  "MENU_PLANNING_BENEF_INTERV",
  "MENU_DEMANDES_DEMANDEUR",
  "MENU_DEMANDES_COMMISSION",
  "MENU_SERVICES_FAITS",
  "MENU_REFERENT_AMENAGEMENTS",
  "MENU_NOTIFICATIONS",
  "MENU_UTILISATEUR_MON_PROFIL",
  "MENU_UTILISATEUR_ADMINISTRATION",
  "MENU_UTILISATEUR_BILANS",
  "MENU_UTILISATEUR_DECONNEXION",
  "MENU_UTILISATEUR_RECUP_IDENTITE",
  "MENU_THEME",
  "MENU_THEME_CLAIR",
  "MENU_THEME_SOMBRE",
  "MENU_THEME_SYSTEME",
  "MENU_A11Y",
  "MENU_A11Y_CONTRASTE",
  "MENU_A11Y_POLICE_LEXEND",
  "MENU_A11Y_POLICE_ARIAL",
  "MENU_A11Y_POLICE_OPENDYS",
  "MENU_A11Y_POLICE_LARGE",
];

function aplatir(nodes: MenuLabelNode[]): string[] {
  return nodes.flatMap((node) => [node.cle, ...(node.enfants ? aplatir(node.enfants) : [])]);
}

describe("menusAdminConfig", () => {
  it("couvre exactement les 32 clés MENU_* créées par la migration, sans en oublier ni en inventer", () => {
    const clesConfig = MENUS_ADMIN_CONFIG.flatMap((onglet) => aplatir(onglet.items));
    const clesUniques = new Set(clesConfig);

    expect([...clesUniques].sort()).toEqual([...CLES_MENU_ATTENDUES].sort());
  });

  it("ne répète jamais une clé au sein d'un même onglet", () => {
    MENUS_ADMIN_CONFIG.forEach((onglet) => {
      const cles = aplatir(onglet.items);
      expect(new Set(cles).size).toBe(cles.length);
    });
  });

  it("chaque onglet a une clé et un libellé non vides", () => {
    MENUS_ADMIN_CONFIG.forEach((onglet) => {
      expect(onglet.key.trim()).not.toBe("");
      expect(onglet.label.trim()).not.toBe("");
    });
  });

  it("l'onglet Administrateur regroupe les items réservés aux admins, absents de l'onglet Commun", () => {
    const administrateur = MENUS_ADMIN_CONFIG.find((o) => o.key === "administrateur");
    const commun = MENUS_ADMIN_CONFIG.find((o) => o.key === "commun");

    expect(aplatir(administrateur?.items ?? [])).toEqual(
      expect.arrayContaining([
        "MENU_UTILISATEUR_ADMINISTRATION",
        "MENU_UTILISATEUR_RECUP_IDENTITE",
      ]),
    );
    expect(aplatir(commun?.items ?? [])).not.toContain("MENU_UTILISATEUR_ADMINISTRATION");
    expect(aplatir(commun?.items ?? [])).not.toContain("MENU_UTILISATEUR_RECUP_IDENTITE");
  });

  it("chaque noeud a un libellé par défaut non vide, pour éviter tout 'Non configuré'", () => {
    function verifierDefauts(nodes: MenuLabelNode[]): void {
      nodes.forEach((node) => {
        expect(node.defaut.trim()).not.toBe("");
        if (node.enfants) verifierDefauts(node.enfants);
      });
    }
    MENUS_ADMIN_CONFIG.forEach((onglet) => verifierDefauts(onglet.items));
  });

  it("scinde le rôle Planificateur en Gestionnaire/Renfort, sans badge 'Visible si Gestionnaire/Renfort'", () => {
    expect(MENUS_ADMIN_CONFIG.some((o) => o.key === "planificateur")).toBe(false);

    const gestionnaire = MENUS_ADMIN_CONFIG.find((o) => o.key === "gestionnaire");
    const renfort = MENUS_ADMIN_CONFIG.find((o) => o.key === "renfort");
    expect(gestionnaire).toBeDefined();
    expect(renfort).toBeDefined();
    expect(aplatir(renfort?.items ?? [])).toContain("MENU_PLANNING_MES_INTERVENTIONS");

    function conditions(nodes: MenuLabelNode[]): string[] {
      return nodes.flatMap((node) => [
        ...(node.condition ? [node.condition] : []),
        ...(node.enfants ? conditions(node.enfants) : []),
      ]);
    }
    const toutesLesConditions = MENUS_ADMIN_CONFIG.flatMap((onglet) => conditions(onglet.items));
    expect(toutesLesConditions.some((c) => /visible si gestionnaire/i.test(c))).toBe(false);
    expect(toutesLesConditions.some((c) => /visible si renfort/i.test(c))).toBe(false);
  });

  it("exclut les items Demandes (et les onglets qui n'auraient plus que ça) quand REACT_APP_GERER_DEMANDES est désactivé", async () => {
    vi.resetModules();
    vi.doMock("@/env", () => ({ env: makeTestEnv({ REACT_APP_GERER_DEMANDES: false }) }));

    const { MENUS_ADMIN_CONFIG: configSansDemandes } =
      await import("@controls/Admin/Menus/menusAdminConfig");

    expect(configSansDemandes.some((o: { key: string }) => o.key === "demandeur")).toBe(false);
    expect(configSansDemandes.some((o: { key: string }) => o.key === "commission")).toBe(false);
    const gestionnaireSansDemandes = configSansDemandes.find(
      (o: { key: string }) => o.key === "gestionnaire",
    );
    expect(aplatir(gestionnaireSansDemandes?.items ?? [])).not.toContain("MENU_DEMANDEURS");

    vi.doUnmock("@/env");
    vi.resetModules();
  });
});
