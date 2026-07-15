/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { env } from "@/env";
import { OngletMenuConfig } from "@controls/Admin/Menus/menusAdminConfig.types";

/**
 * Mapping des 32 paramètres MENU_* (cf. migration Version20260710120000) vers les onglets
 * d'administration, organisés par rôle applicatif comme dans AppLayoutMenu.tsx.
 *
 * Le rôle "Planificateur" du code (`isPlanificateur = isGestionnaire || isRenfort`) est scindé
 * ici en deux onglets distincts (Gestionnaire / Renfort) : plus parlant pour un admin qui ne
 * connaît pas ce nom technique, et qui n'a donc plus besoin d'un badge "Visible si..." pour
 * comprendre à qui un item s'adresse — l'appartenance à l'onglet suffit.
 */
function construireConfig(): OngletMenuConfig[] {
  const config: OngletMenuConfig[] = [
    {
      key: "gestionnaire",
      label: "Gestionnaire",
      role: "gestionnaire",
      accent: "#1e88e5",
      items: [
        ...(env.REACT_APP_GERER_DEMANDES ? [{ cle: "MENU_DEMANDEURS", defaut: "Demandeurs" }] : []),
        {
          cle: "MENU_BENEFICIAIRES",
          defaut: "Bénéficiaires",
          enfants: [
            { cle: "MENU_BENEFICIAIRES_LISTE", defaut: "Bénéficiaires" },
            { cle: "MENU_BENEFICIAIRES_AMENAGEMENT_PAR_BENEF", defaut: "Aménagements par bénéf." },
            { cle: "MENU_BENEFICIAIRES_AMENAGEMENT", defaut: "Aménagements" },
          ],
        },
        { cle: "MENU_INTERVENANTS", defaut: "Intervenants" },
        {
          cle: "MENU_PLANNING_PLANIF",
          defaut: "Planning",
          enfants: [
            { cle: "MENU_PLANNING_INTERVENTIONS", defaut: "Planning des interventions" },
            {
              cle: "MENU_PLANNING_INTERVENTIONS_FORFAIT",
              defaut: "Interventions au forfait (prise de notes)",
            },
            {
              cle: "MENU_PLANNING_VALIDATION_RENFORTS",
              defaut: "Validation des interventions des renforts",
            },
          ],
        },
        { cle: "MENU_NOTIFICATIONS", defaut: "Notifications" },
        { cle: "MENU_UTILISATEUR_BILANS", defaut: "Bilans" },
      ],
    },
    {
      key: "renfort",
      label: "Renfort",
      role: "renfort",
      accent: "var(--color-renfort)",
      items: [{ cle: "MENU_PLANNING_MES_INTERVENTIONS", defaut: "Vos interventions (renfort)" }],
    },
    {
      key: "beneficiaire",
      label: "Bénéficiaire",
      role: "beneficiaire",
      accent: "var(--color-beneficiaire)",
      items: [
        {
          cle: "MENU_PLANNING_BENEF_INTERV",
          defaut: "Planning",
          condition: "Libellé partagé avec l'onglet Intervenant (même menu, même paramètre)",
        },
        {
          cle: "MENU_UTILISATEUR_MON_PROFIL",
          defaut: "Mon profil",
          condition: "Libellé partagé avec l'onglet Intervenant (même menu, même paramètre)",
        },
      ],
    },
    {
      key: "intervenant",
      label: "Intervenant",
      role: "intervenant",
      accent: "var(--color-intervenant)",
      items: [
        {
          cle: "MENU_PLANNING_BENEF_INTERV",
          defaut: "Planning",
          condition: "Libellé partagé avec l'onglet Bénéficiaire (même menu, même paramètre)",
        },
        { cle: "MENU_SERVICES_FAITS", defaut: "Services faits" },
        {
          cle: "MENU_UTILISATEUR_MON_PROFIL",
          defaut: "Mon profil",
          condition: "Libellé partagé avec l'onglet Bénéficiaire (même menu, même paramètre)",
        },
      ],
    },
    {
      key: "referent",
      label: "Référent de composante",
      role: "referent",
      accent: "#f4511e",
      items: [{ cle: "MENU_REFERENT_AMENAGEMENTS", defaut: "Aménagements" }],
    },
    {
      key: "administrateur",
      label: "Administrateur",
      role: "administrateur",
      accent: "#5e35b1",
      items: [
        { cle: "MENU_UTILISATEUR_ADMINISTRATION", defaut: "Administration" },
        {
          cle: "MENU_UTILISATEUR_RECUP_IDENTITE",
          defaut: "Récupérer mon identité",
          condition: "Visible en cours d'usurpation d'identité",
        },
      ],
    },
    {
      key: "commun",
      label: "Commun",
      role: "commun",
      accent: "#546e7a",
      items: [
        { cle: "MENU_UTILISATEUR_DECONNEXION", defaut: "Déconnexion" },
        {
          cle: "MENU_THEME",
          defaut: "Thème",
          condition: "Visible si REACT_APP_DARKMODE activé",
          enfants: [
            { cle: "MENU_THEME_CLAIR", defaut: "Mode clair" },
            { cle: "MENU_THEME_SOMBRE", defaut: "Mode sombre" },
            { cle: "MENU_THEME_SYSTEME", defaut: "Identique au système" },
          ],
        },
        {
          cle: "MENU_A11Y",
          defaut: "Accessibilité",
          enfants: [
            { cle: "MENU_A11Y_CONTRASTE", defaut: "Contraste" },
            { cle: "MENU_A11Y_POLICE_LEXEND", defaut: "Police : Lexend" },
            { cle: "MENU_A11Y_POLICE_ARIAL", defaut: "Police : Arial" },
            { cle: "MENU_A11Y_POLICE_OPENDYS", defaut: "Police : OpenDys" },
            { cle: "MENU_A11Y_POLICE_LARGE", defaut: "Police large" },
          ],
        },
      ],
    },
  ];

  if (env.REACT_APP_GERER_DEMANDES) {
    // Ordre final visé : Gestionnaire, Renfort, Demandeur, Bénéficiaire, Intervenant,
    // Membre de commission, Référent, Administrateur, Commun.
    const indexBeneficiaire = config.findIndex((onglet) => onglet.key === "beneficiaire");
    config.splice(indexBeneficiaire, 0, {
      key: "demandeur",
      label: "Demandeur",
      role: "demandeur",
      accent: "var(--color-demandeur)",
      items: [{ cle: "MENU_DEMANDES_DEMANDEUR", defaut: "Demandes" }],
    });

    const indexReferent = config.findIndex((onglet) => onglet.key === "referent");
    config.splice(indexReferent, 0, {
      key: "commission",
      label: "Membre de commission",
      role: "commission",
      accent: "#00897b",
      items: [{ cle: "MENU_DEMANDES_COMMISSION", defaut: "Demandes" }],
    });
  }

  return config;
}

export const MENUS_ADMIN_CONFIG: OngletMenuConfig[] = construireConfig();
