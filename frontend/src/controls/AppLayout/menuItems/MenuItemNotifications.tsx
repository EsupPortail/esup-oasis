/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { NavigateFunction } from "react-router-dom";
import { Badge, Button, MenuProps } from "antd";
import { BellOutlined } from "@ant-design/icons";
import { IStatistiquesEvenements } from "@api";
import { Utilisateur } from "@lib";
import {
  IAffichage,
  IFiltresEvenements,
  PlanningLayout,
  TypeAffichageValues,
} from "@context/affichageFiltres/AffichageFiltresContext";
import { AffectationFilterValues } from "@controls/Filters/Affectation/AffectationFilter";
import { BENEFICIAIRE_PROFIL_A_DETERMINER } from "@/constants";
import { EtatAvisEse } from "@controls/Avatars/BeneficiaireAvisEseAvatar";
import { EtatDecisionEtablissement } from "@controls/Avatars/DecisionEtablissementAvatar";
import { env } from "@/env";
import { createNotificationItem } from "@controls/AppLayout/menuItems/MenuItemNotifications.utils";

type MenuItems = MenuProps["items"];

/**
 * Navigates to the calendar page with the specified display type, assignment filter, and layout.
 */
function goToCalendar(
  type: TypeAffichageValues,
  affecte: AffectationFilterValues,
  layout: PlanningLayout,
  setAffichageFiltres: (
    affichage: Partial<IAffichage>,
    filtres: Partial<IFiltresEvenements>,
  ) => void,
  navigate: NavigateFunction,
): void {
  setAffichageFiltres(
    {
      type,
      layout,
    },
    {
      debut: new Date(),
      fin: new Date(),
      "exists[intervenant]": affecte,
    },
  );
  navigate(`/planning/calendrier`);
}

/**
 * Generates planification notification items.
 */
function getPlanificationItems(
  stats: IStatistiquesEvenements,
  setAffichageFiltres: (
    affichage: Partial<IAffichage>,
    filtres: Partial<IFiltresEvenements>,
  ) => void,
  navigate: NavigateFunction,
): MenuItems {
  return [
    {
      key: "notifications-planification",
      type: "group",
      label: `Notifications ${env.REACT_APP_TITRE}`.toLocaleUpperCase(),
      style: { fontWeight: 600, lineHeight: 1, marginTop: 10 },
      className: "text-text",
    },
    createNotificationItem({
      key: "notifications-aujourdhui",
      count: stats.evenementsNonAffectesJour,
      libelles: [
        "Tous les évènements du jour sont affectés",
        "1 évènement à affecter aujourd'hui",
        "{count} évènements à affecter aujourd'hui",
      ],
      severity: "error",
      onClick: () => {
        goToCalendar(
          "day",
          AffectationFilterValues.NonAffectes,
          PlanningLayout.table,
          setAffichageFiltres,
          navigate,
        );
      },
    }),
    createNotificationItem({
      key: "notifications-semaine",
      count: stats.evenementsNonAffectesSemaine,
      libelles: [
        "Tous les évènements de la semaine sont affectés",
        "1 évènement à affecter cette semaine",
        "{count} évènements à affecter cette semaine",
      ],
      onClick: () => {
        goToCalendar(
          "week",
          AffectationFilterValues.NonAffectes,
          PlanningLayout.table,
          setAffichageFiltres,
          navigate,
        );
      },
    }),
  ];
}

/**
 * Generates gestionnaire notification items.
 */
function getGestionnaireItems(
  user: Utilisateur,
  stats: IStatistiquesEvenements,
  navigate: NavigateFunction,
): MenuItems {
  if (!user.isGestionnaire) return [];

  return [
    { key: "divider-renfort", type: "divider" },
    createNotificationItem({
      key: "notifications-renforts",
      count: stats.evenementsEnAttenteDeValidation,
      libelles: [
        "Toutes les interventions sont validées",
        "1 intervention à valider",
        "{count} interventions à valider",
      ],
      onClick: () => navigate(`/interventions/renforts`),
    }),
    { key: "divider-profils", type: "divider" },
    createNotificationItem({
      key: "notifications-profils",
      count: stats.nbBeneficiairesIncomplets,
      libelles: [
        "Tous les profils des bénéficiaires sont renseignés",
        "1 bénéficiaire avec profil à renseigner",
        "{count} bénéficiaires avec profil à renseigner",
      ],
      onClick: () =>
        navigate(
          `/beneficiaires?filtreType=profil&filtreValeur=${BENEFICIAIRE_PROFIL_A_DETERMINER}`,
        ),
    }),
    createNotificationItem({
      key: "notifications-avisEse",
      count: stats.nbAvisEseEnAttente,
      libelles: [
        `Tous les avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} attendus sont renseignés`,
        `1 bénéficiaire avec avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} attendu`,
        `{count} bénéficiaires avec avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} attendu`,
      ],
      onClick: () =>
        navigate(
          `/beneficiaires?filtreType=etatAvisEse&filtreValeur=${EtatAvisEse.ETAT_EN_ATTENTE}`,
        ),
    }),
    createNotificationItem({
      key: "notifications-decision-valider",
      count: stats.nbDecisionsAttenteValidation,
      libelles: [
        "Toutes les décisions d'étab. sont validées",
        "1 décision d'étab. à valider",
        "{count} décisions d'étab. à valider",
      ],
      onClick: () =>
        navigate(
          `/beneficiaires?filtreType=etatDecisionAmenagement&filtreValeur=${EtatDecisionEtablissement.ATTENTE_VALIDATION_CAS}`,
        ),
    }),
  ];
}

/**
 * Generates admin notification items.
 */
function getAdminItems(
  user: Utilisateur,
  stats: IStatistiquesEvenements,
  navigate: NavigateFunction,
): MenuItems {
  if (!user.isAdmin) return [];

  return [
    createNotificationItem({
      key: "notifications-decision-editer",
      count: stats.nbDecisionsAEditer,
      libelles: [
        "Toutes les décisions d'étab. sont éditées",
        "1 décision d'étab. à éditer",
        "{count} décisions d'étab. à éditer",
      ],
      onClick: () =>
        navigate(
          `/beneficiaires?filtreType=etatDecisionAmenagement&filtreValeur=${EtatDecisionEtablissement.VALIDE}`,
        ),
    }),
  ];
}

/**
 * Retrieves the menu items for the notifications menu
 */
export function menuItemNotifications(
  user: Utilisateur,
  navigate: NavigateFunction,
  setAffichageFiltres: (
    affichage: Partial<IAffichage>,
    filtres: Partial<IFiltresEvenements>,
  ) => void,
  stats: IStatistiquesEvenements | undefined,
  isFetchingStats: boolean,
  labels?: Record<string, string>,
): MenuProps["items"] | null {
  const libelleNotifications = labels?.MENU_NOTIFICATIONS ?? "Notifications";
  const notificationCount =
    (stats?.evenementsNonAffectesJour || 0) +
    (stats?.evenementsNonAffectesSemaine || 0) +
    (user?.isGestionnaire ? stats?.nbBeneficiairesIncomplets || 0 : 0) +
    (user?.isGestionnaire ? stats?.evenementsEnAttenteDeValidation || 0 : 0) +
    (user?.isGestionnaire ? stats?.nbAvisEseEnAttente || 0 : 0) +
    (user?.isGestionnaire ? stats?.nbDecisionsAttenteValidation || 0 : 0) +
    (user?.isAdmin ? stats?.nbDecisionsAEditer || 0 : 0);

  if (isFetchingStats || !stats) {
    return [
      {
        key: "notifications",
        label: (
          <>
            <BellOutlined className="hide-on-overflow" />
            <span className="show-on-overflow ml-0">{libelleNotifications}</span>
          </>
        ),
        className: "menu-small-item no-indicator notifications",
        children: [],
      },
    ];
  }

  const children: MenuProps["items"] = [
    ...(getPlanificationItems(stats, setAffichageFiltres, navigate) || []),
    ...(getGestionnaireItems(user, stats, navigate) || []),
    ...(getAdminItems(user, stats, navigate) || []),
  ];

  return [
    {
      key: "notifications",
      label: (
        <Button
          type="text"
          className="bg-transparent p-0"
          aria-label="Notifications de l'application"
        >
          <Badge
            count={notificationCount}
            size="small"
            offset={[-17, 15]}
            className="hide-on-overflow"
          >
            <BellOutlined className="hide-on-overflow" />
          </Badge>
          <span className="show-on-overflow">{libelleNotifications}</span>
        </Button>
      ),
      className: "menu-small-item no-indicator notifications",
      children,
    },
  ];
}
