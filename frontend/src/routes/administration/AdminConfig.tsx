/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import {
  AppstoreAddOutlined,
  BankOutlined,
  CalendarOutlined,
  CrownOutlined,
  DeploymentUnitOutlined,
  DribbbleOutlined,
  FileDoneOutlined,
  FolderOutlined,
  IdcardOutlined,
  PercentageOutlined,
  ProductOutlined,
  SignatureOutlined,
  TagOutlined,
  TeamOutlined,
  ToolOutlined,
  UnorderedListOutlined,
} from "@ant-design/icons";
import { APIPathsReferentiel } from "@api";
import { AdminPanel } from "@controls/Admin/AdminPanel";
import React from "react";
import MedalIcon from "@/assets/images/medal.svg?react";
import ReferentIcon from "@/assets/images/referent.svg?react";
import ArtIcon from "@/assets/images/art.svg?react";
import { env } from "@/env";

/**
 * Represents a configuration object for referentiel.
 */
export type AdminConfig = {
  id: string;
  title: string;
  description: string;
  disabled?: boolean;
  icon: React.ComponentType;
  apiPath?: APIPathsReferentiel;
  categorie: string;
};

/**
 * Represents the configuration for all referentials.
 */
const ADMIN_CONFIG: AdminConfig[] = [
  {
    id: "types-evenements",
    title: "Catégories évènements",
    description: "Liste des catégories et tarification associée",
    icon: FolderOutlined,
    categorie: "planification",
  },
  {
    id: "periodes-rh",
    title: "Périodes RH",
    description: "Périodes pour remontées RH",
    icon: CalendarOutlined,
    categorie: "RH",
  },
  {
    id: "campus",
    title: "Campus",
    description: "Listes des campus de l'établissement (lieux des évènements)",
    icon: BankOutlined,
    apiPath: "/campus",
    categorie: "planification",
  },
  {
    id: "services",
    title: `Bureaux ${env.REACT_APP_SERVICE}`,
    description: "Liste des bureaux",
    icon: DeploymentUnitOutlined,
    apiPath: "/services",
    categorie: "utilisateurs",
  },
  {
    id: "utilisateurs",
    title: "Utilisateurs",
    description: "Administrateurs, chargé•es d'accompagnement et renforts",
    icon: TeamOutlined,
    categorie: "utilisateurs",
  },
  {
    id: "referents",
    title: "Référents",
    description: "Référents de composante",
    icon: ReferentIcon,
    categorie: "utilisateurs",
  },
  {
    id: "parametres",
    title: "Paramètres",
    description: "Constantes utilisées par l'application",
    icon: PercentageOutlined,
    categorie: "parametres",
  },
  {
    id: "menus",
    title: "Menus",
    description: "Menus de l'application",
    icon: UnorderedListOutlined,
    categorie: "parametres",
  },
  {
    id: "competences",
    title: "Compétences",
    description: "Compétences des intervenants",
    icon: CrownOutlined,
    apiPath: "/competences",
    categorie: "intervenants",
  },
  {
    id: "profils",
    title: "Profils",
    description: "Profils des bénéficiaires",
    icon: IdcardOutlined,
    categorie: "bénéficiaires",
  },
  {
    id: "equipements",
    title: "Equipements",
    description: "Types d'équipements nécessaires pour un évènement",
    icon: ToolOutlined,
    apiPath: "/types_equipements",
    categorie: "planification",
  },
  {
    id: "typologies-handicap",
    title: "Typologies handicap",
    description: "Typologies de handicap liées au profil des bénéficiaires",
    icon: IdcardOutlined,
    apiPath: "/typologies",
    categorie: "bénéficiaires",
  },
  {
    id: "disciplines-sportives",
    title: "Disciplines sportives",
    description: "Disciplines sportives pratiquées par les bénéficiaires",
    icon: DribbbleOutlined,
    apiPath: "/disciplines_sportives",
    categorie: "demandes",
  },
  {
    id: "sportifs-haut-niveau",
    title: "Sportifs haut niveau",
    description: "Liste des sportifs de haut niveau (avec numéro PSQS)",
    icon: MedalIcon,
    categorie: "demandes",
  },
  {
    id: "types_demandes",
    title: "Campagnes de demandes",
    description: "Types de demandes et campagnes associées",
    icon: FileDoneOutlined,
    categorie: "demandes",
  },
  {
    id: "commissions",
    title: "Commissions",
    description: "Membres des commissions",
    icon: ProductOutlined,
    categorie: "demandes",
  },
  {
    id: "types-amenagements",
    title: "Aménagements",
    description: "Catégories et types d'aménagements",
    icon: AppstoreAddOutlined,
    categorie: "bénéficiaires",
  },
  {
    id: "types-suivis-amenagements",
    title: "Aménagements : Suivi mise en place",
    description: "Suivis associés aux aménagements des bénéficiaires",
    icon: AppstoreAddOutlined,
    categorie: "bénéficiaires",
    apiPath: "/types_suivi_amenagements",
  },
  {
    id: "clubs-sportifs",
    title: "Clubs sportifs",
    description: "Clubs sportifs, centres de formations",
    icon: DribbbleOutlined,
    categorie: "demandes",
  },
  {
    id: "disciplines-artistiques",
    title: "Disciplines artistiques",
    description: "Disciplines artistiques pratiquées par les bénéficiaires",
    icon: ArtIcon,
    categorie: "demandes",
    apiPath: "/disciplines_artistiques",
  },
  {
    id: "etablissements-enseignement-artistique",
    title: "Etab. artistiques",
    description: "Établissements d'enseignement artistique par les bénéficiaires",
    icon: BankOutlined,
    categorie: "demandes",
    apiPath: "/etablissements_enseignement_artistique",
  },
  {
    id: "chartes",
    title: "Chartes",
    description: "Chartes validées par les bénéficiaires",
    icon: SignatureOutlined,
    categorie: "demandes",
  },
  {
    id: "tags",
    title: "Tags",
    description: "Tags associés bénéficiaires",
    icon: TagOutlined,
    categorie: "bénéficiaires",
  },
];

export function getAdminPanelsById(panelIds: string[]) {
  return ADMIN_CONFIG.filter((r) => panelIds.includes(r.id))
    .sort((r1, r2) => r1.title.localeCompare(r2.title))
    .map((referentiel) => (
      <AdminPanel
        key={referentiel.id}
        title={referentiel.title}
        description={referentiel.description}
        onClickUrl={`/administration/referentiels/${referentiel.id}`}
        icon={referentiel.icon}
        disabled={referentiel.disabled}
      />
    ));
}

export function getAdminPanelsByCategorie(categorie: string) {
  return getAdminPanelsById(ADMIN_CONFIG.filter((r) => r.categorie === categorie).map((r) => r.id));
}

export default ADMIN_CONFIG;
