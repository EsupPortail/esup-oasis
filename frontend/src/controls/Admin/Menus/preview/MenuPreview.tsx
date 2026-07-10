/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Card, Menu, MenuProps } from "antd";
import { Utilisateur } from "@lib";
import { AuthContextType } from "@/auth/AuthProvider";
import { MenuLabelNode, OngletMenuConfig } from "@controls/Admin/Menus/menusAdminConfig.types";
import { MENUS_ADMIN_CONFIG } from "@controls/Admin/Menus/menusAdminConfig";
import { mockUtilisateurPourOnglet } from "@controls/Admin/Menus/preview/mockUtilisateurs";
import { menuItemNotifications } from "@controls/AppLayout/menuItems/MenuItemNotifications";
import { menuItemTheme } from "@controls/AppLayout/menuItems/MenuItemTheme";
import { buildRoleMenuItems } from "@controls/AppLayout/buildRoleMenuItems";
import { menuItemAccessibilite } from "@controls/AppLayout/menuItems/MenuItemAccessibilite";
import { menuItemUtilisateur } from "@controls/AppLayout/menuItems/MenuItemUtilisateur";
import { DARKMODE_ENABLED } from "@utils/theme/useEffectiveTheme";

interface MenuPreviewProps {
  onglet: OngletMenuConfig;
  labels: Record<string, string>;
}

const NOOP = (): void => {};
const ACCESSIBILITE_INACTIVE = {
  contrast: false,
  dyslexieArial: false,
  dyslexieOpenDys: false,
  dyslexieLexend: false,
  policeLarge: false,
};

function buildAuthApercu(user: Utilisateur, estUsurpation: boolean): AuthContextType {
  return {
    user,
    signOut: NOOP,
    loading: false,
    error: null,
    authenticate: NOOP,
    setUser: NOOP,
    token: undefined,
    // Non undefined uniquement pour l'onglet Administrateur : "Récupérer mon identité" ne doit
    // apparaître que là, l'usurpation étant une capacité réservée aux admins.
    impersonate: estUsurpation ? "preview" : undefined,
    setImpersonate: NOOP,
    removeImpersonate: NOOP,
  };
}

/**
 * Items transverses (Thème, Accessibilité, menu utilisateur), visibles quel que soit le rôle —
 * mêmes fonctions menuItemXxx que AppLayoutMenu.tsx, donc les mêmes conditions de visibilité par
 * rôle s'appliquent (ex. "Administration" seulement si isAdmin, "Mon profil" seulement si
 * isBeneficiaire/isIntervenant).
 */
function buildCommunItems(
  user: Utilisateur,
  labels: Record<string, string>,
  estUsurpation: boolean,
): MenuProps["items"] {
  return [
    ...(DARKMODE_ENABLED ? (menuItemTheme("light", NOOP, NOOP, labels) ?? []) : []),
    ...(menuItemAccessibilite(ACCESSIBILITE_INACTIVE, NOOP, NOOP, NOOP, NOOP, NOOP, NOOP, labels) ??
      []),
    ...menuItemUtilisateur(NOOP, buildAuthApercu(user, estUsurpation), 0, NOOP, labels),
  ];
}

type MenuItem = NonNullable<MenuProps["items"]>[number];

/** Couleur d'accent du rôle propriétaire de chaque clé MENU_*, dérivée de MENUS_ADMIN_CONFIG. */
const ACCENT_PAR_CLE: Record<string, string> = (() => {
  const accents: Record<string, string> = {};
  const indexer = (nodes: MenuLabelNode[], accent: string): void => {
    nodes.forEach((node) => {
      // Premier onglet trouvé qui porte la clé gagne (ex. MENU_PLANNING_BENEF_INTERV est
      // listé à la fois sous Bénéficiaire et Intervenant, tous deux avec des couleurs
      // différentes : on retient la couleur du premier, de façon déterministe).
      if (!(node.cle in accents)) accents[node.cle] = accent;
      if (node.enfants) indexer(node.enfants, accent);
    });
  };
  MENUS_ADMIN_CONFIG.forEach((onglet) => indexer(onglet.items, onglet.accent));
  return accents;
})();

/**
 * Correspondance entre la clé technique `key` des items antd (définie dans les menuItemXxx de
 * production) et la clé du paramètre MENU_* qu'ils affichent — nécessaire pour savoir, dans cet
 * aperçu, dans quel onglet chaque item est réellement modifiable. La clé antd "planning" est
 * ambiguë (réutilisée par deux menuItemXxx différents) et résolue séparément selon le rôle simulé.
 */
const CLE_MENU_PAR_CLE_ANTD: Record<string, string> = {
  demandeurs: "MENU_DEMANDEURS",
  beneficiaires: "MENU_BENEFICIAIRES",
  "beneficiaires-item": "MENU_BENEFICIAIRES_LISTE",
  "amenagements-beneficiaires": "MENU_BENEFICIAIRES_AMENAGEMENT_PAR_BENEF",
  amenagements: "MENU_BENEFICIAIRES_AMENAGEMENT",
  intervenants: "MENU_INTERVENANTS",
  "planning-item": "MENU_PLANNING_INTERVENTIONS",
  "interventions-forfait": "MENU_PLANNING_INTERVENTIONS_FORFAIT",
  "interventions-renforts": "MENU_PLANNING_VALIDATION_RENFORTS",
  "mes-interventions": "MENU_PLANNING_MES_INTERVENTIONS",
  demandes: "MENU_DEMANDES_DEMANDEUR",
  "services-faits": "MENU_SERVICES_FAITS",
  "demandeurs-commission": "MENU_DEMANDES_COMMISSION",
  "beneficiaires-referent": "MENU_REFERENT_AMENAGEMENTS",
  notifications: "MENU_NOTIFICATIONS",
  theme: "MENU_THEME",
  "theme-light": "MENU_THEME_CLAIR",
  "theme-dark": "MENU_THEME_SOMBRE",
  "theme-system": "MENU_THEME_SYSTEME",
  accessibilite: "MENU_A11Y",
  "accessibilite-contraste": "MENU_A11Y_CONTRASTE",
  "accessibilite-dyslexie-lexend": "MENU_A11Y_POLICE_LEXEND",
  "accessibilite-dyslexie": "MENU_A11Y_POLICE_ARIAL",
  "accessibilite-dyslexie-opendys": "MENU_A11Y_POLICE_OPENDYS",
  "accessibilite-police-large": "MENU_A11Y_POLICE_LARGE",
  "mon-profil": "MENU_UTILISATEUR_MON_PROFIL",
  admin: "MENU_UTILISATEUR_ADMINISTRATION",
  bilans: "MENU_UTILISATEUR_BILANS",
  exit: "MENU_UTILISATEUR_DECONNEXION",
  impersonate: "MENU_UTILISATEUR_RECUP_IDENTITE",
};

function cleMenuPourItem(cleAntd: string, user: Utilisateur): string | undefined {
  if (cleAntd === "planning") {
    return user.isPlanificateur ? "MENU_PLANNING_PLANIF" : "MENU_PLANNING_BENEF_INTERV";
  }
  return CLE_MENU_PAR_CLE_ANTD[cleAntd];
}

function avecPastille(label: React.ReactNode, accent: string): React.ReactNode {
  return (
    <span className="menu-preview-item">
      <span className="menus-admin-tab-dot" style={{ background: accent }} aria-hidden />
      {label}
    </span>
  );
}

/**
 * Prépare les items pour l'aperçu : retire les `children` vides (dans le menu horizontal de
 * production, ces items comme "Intervenants" n'ont pas de flèche grâce à
 * `popupClassName: "d-none"`, une astuce propre au mode horizontal — absente en vertical, d'où une
 * flèche de sous-menu vide sans ce nettoyage) et ajoute une pastille de la couleur du rôle
 * propriétaire devant chaque libellé, pour indiquer où il est réellement modifiable.
 */
function preparerPourApercu(items: MenuProps["items"], user: Utilisateur): MenuItem[] {
  return (items ?? []).map((item): MenuItem => {
    if (!item) return item;

    let itemPrepare: MenuItem = item;

    if ("children" in item && Array.isArray(item.children)) {
      if (item.children.length === 0) {
        itemPrepare = { ...item };
        delete (itemPrepare as unknown as Record<string, unknown>).children;
      } else {
        itemPrepare = { ...item, children: preparerPourApercu(item.children, user) };
      }
    }

    const cleMenu =
      "key" in itemPrepare && itemPrepare.key
        ? cleMenuPourItem(String(itemPrepare.key), user)
        : undefined;
    const accent = cleMenu ? ACCENT_PAR_CLE[cleMenu] : undefined;
    if (accent && "label" in itemPrepare) {
      itemPrepare = { ...itemPrepare, label: avecPastille(itemPrepare.label, accent) };
    }

    return itemPrepare;
  });
}

/**
 * Construit les items du VRAI menu (mêmes fonctions menuItemXxx que AppLayoutMenu.tsx) pour un
 * utilisateur simulé, afin que l'aperçu soit fidèle à ce qui sera affiché en production.
 */
function buildItems(onglet: OngletMenuConfig, labels: Record<string, string>): MenuProps["items"] {
  const user = mockUtilisateurPourOnglet(onglet.role);
  const navigate = NOOP;
  const estUsurpation = onglet.key === "administrateur";

  // Onglet "Commun" : uniquement les items transverses, avec un mock cumulant plusieurs rôles
  // pour révéler tous les sous-items conditionnels d'un coup (cf. mockUtilisateurs.ts).
  if (onglet.key === "commun") return buildCommunItems(user, labels, estUsurpation);

  // Tous les autres onglets : items propres au rôle + items transverses, exactement comme un
  // utilisateur réel les verrait en production (le menu commun est toujours présent).
  return [
    ...(buildRoleMenuItems(user, navigate, NOOP, labels) ?? []),
    // Notifications visibles pour tout planificateur (isGestionnaire || isRenfort en production).
    ...(onglet.key === "gestionnaire" || onglet.key === "renfort"
      ? (menuItemNotifications(user, navigate, NOOP, undefined, false, labels) ?? [])
      : []),
    ...(buildCommunItems(user, labels, estUsurpation) ?? []),
  ];
}

/** Aperçu live du menu réel de production pour un onglet (un rôle applicatif) donné. */
export default function MenuPreview({ onglet, labels }: MenuPreviewProps): ReactElement {
  const user = mockUtilisateurPourOnglet(onglet.role);

  return (
    <Card className="menu-preview__frame" size="small" title="Menu principal">
      <Menu
        aria-label={`Aperçu du menu ${onglet.label}`}
        mode="vertical"
        selectable={false}
        className="menu-preview__menu"
        items={preparerPourApercu(buildItems(onglet, labels), user)}
      />
    </Card>
  );
}
