/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Utilisateur } from "../../../lib/Utilisateur";
import { NavigateFunction } from "react-router-dom";
import { Dispatch } from "redux";
import { IStatistiquesEvenements } from "../../../api/ApiTypeHelpers";
import { Badge, Button, MenuProps } from "antd";
import { PlanningLayout, TypeAffichageValues } from "../../../redux/context/IAffichageFiltres";
import { AffectationFilterValues } from "../../Filters/Affectation/AffectationFilter";
import { setAffichageFiltres } from "../../../redux/actions/AffichageFiltre";
import { BellOutlined, CheckCircleFilled, WarningFilled } from "@ant-design/icons";
import { BENEFICIAIRE_PROFIL_A_DETERMINER } from "../../../constants";
import { EtatAvisEse } from "../../Avatars/BeneficiaireAvisEseAvatar";
import { EtatDecisionEtablissement } from "../../Avatars/DecisionEtablissementAvatar";
import React from "react";
import { env } from "../../../env";

/**
 * Retrieves the menu items for the notifications menu
 * @param {Utilisateur} user - The user object
 * @param {NavigateFunction} navigate - The navigate function
 * @param {Dispatch} dispatch - The dispatch function
 * @param {IStatistiquesEvenements | undefined} stats - The statistics object
 * @param {boolean} isFetchingStats - A boolean indicating whether the statistics are being fetched or not
 * @returns {MenuProps["items"]} - The menu items for the notifications menu
 */
export function menuItemNotifications(
   user: Utilisateur,
   navigate: NavigateFunction,
   dispatch: Dispatch,
   stats: IStatistiquesEvenements | undefined,
   isFetchingStats: boolean,
): MenuProps["items"] | null {
   /**
    * Returns the appropriate libelle based on the given count (gestion pluriel).
    * @param {number | undefined} count - The count to determine the libelle.
    * @param {string} libelle0 - The libelle when count is undefined or 0.
    * @param {string} libelle1 - The libelle when count is 1.
    * @param {string} libelleN - The libelle when count is greater than 1, where "{count}" will be replaced with the actual count value.
    * @return {string} The appropriate libelle based on the count.
    */
   function getLibelleByCount(
      count: number | undefined,
      libelle0: string,
      libelle1: string,
      libelleN: string,
   ): string {
      if (count === undefined || count === 0) {
         return libelle0;
      }

      if (count === 1) {
         return libelle1;
      }

      return libelleN.replace("{count}", count.toString());
   }

   /**
    * Navigates to the calendar page with the specified display type, assignment filter, and layout.
    *
    * @param {TypeAffichageValues} type - The display type for the calendar.
    * @param {AffectationFilterValues} affecte - The assignment filter for the calendar.
    * @param {PlanningLayout} layout - The layout for the calendar.
    *
    * @return {void}
    */
   function goToCalendar(
      type: TypeAffichageValues,
      affecte: AffectationFilterValues,
      layout: PlanningLayout,
   ): void {
      dispatch(
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
         ),
      );
      navigate(`/planning/calendrier`);
   }

   if (isFetchingStats || !stats)
      return [
         {
            key: "notifications",
            label: (
               <>
                  <BellOutlined className="hide-on-overflow" />
                  <span className="show-on-overflow ml-0">Notifications</span>
               </>
            ),
            className: "menu-small-item no-indicator notifications",
            children: [],
         },
      ];

   return [
      {
         key: "notifications",
         label: (
            <Button
               type="text"
               className="bg-transparent p-0 pt-1"
               aria-label="Notifications de l'application"
            >
               <Badge
                  count={
                     (stats?.evenementsNonAffectesJour || 0) +
                     (stats?.evenementsNonAffectesSemaine || 0) +
                     (user?.isGestionnaire ? stats?.nbBeneficiairesIncomplets || 0 : 0) +
                     (user?.isGestionnaire ? stats?.evenementsEnAttenteDeValidation || 0 : 0) +
                     (user?.isGestionnaire ? stats?.nbAvisEseEnAttente || 0 : 0) +
                     (user?.isGestionnaire ? stats?.nbDecisionsAttenteValidation || 0 : 0) +
                     (user?.isAdmin ? stats?.nbDecisionsAEditer || 0 : 0)
                  }
                  size="small"
                  offset={[-17, 15]}
                  className={"hide-on-overflow"}
               >
                  <BellOutlined className="hide-on-overflow" />
               </Badge>
               <span className={"show-on-overflow"}>Notifications</span>
            </Button>
         ),
         className: "menu-small-item no-indicator notifications",
         children: [
            {
               key: "notifications-planification",
               type: "group",
               label: `Notifications ${env.REACT_APP_TITRE}`.toLocaleUpperCase(),
               style: { fontWeight: 600, lineHeight: 1, marginTop: 10 },
               className: "text-text",
            },
            {
               key: "notifications-aujourdhui",
               className: stats?.evenementsNonAffectesJour === 0 ? "text-success" : "text-error",
               icon:
                  stats?.evenementsNonAffectesJour === 0 ? (
                     <CheckCircleFilled className="text-success" />
                  ) : (
                     <WarningFilled className="text-error" />
                  ),
               label: getLibelleByCount(
                  stats?.evenementsNonAffectesJour,
                  "Tous les évènements du jour sont affectés",
                  "1 évènement à affecter aujourd'hui",
                  "{count} évènements à affecter aujourd'hui",
               ),
               onClick: () => {
                  goToCalendar("day", AffectationFilterValues.NonAffectes, PlanningLayout.table);
               },
            },
            {
               key: "notifications-semaine",
               className:
                  stats?.evenementsNonAffectesSemaine === 0 ? "text-success" : "text-warning",
               icon:
                  stats?.evenementsNonAffectesSemaine === 0 ? (
                     <CheckCircleFilled />
                  ) : (
                     <WarningFilled />
                  ),
               label: getLibelleByCount(
                  stats?.evenementsNonAffectesSemaine,
                  "Tous les évènements de la semaine sont affectés",
                  "1 évènement à affecter cette semaine",
                  "{count} évènements à affecter cette semaine",
               ),
               onClick: () => {
                  goToCalendar("week", AffectationFilterValues.NonAffectes, PlanningLayout.table);
               },
            },
            user?.isGestionnaire
               ? {
                    key: "divider-renfort",
                    type: "divider",
                 }
               : null,
            user?.isGestionnaire
               ? {
                    key: "notifications-renforts",
                    className:
                       stats?.evenementsEnAttenteDeValidation === 0
                          ? "text-success"
                          : "text-warning",
                    icon:
                       stats?.evenementsEnAttenteDeValidation === 0 ? (
                          <CheckCircleFilled />
                       ) : (
                          <WarningFilled />
                       ),
                    label: getLibelleByCount(
                       stats?.evenementsEnAttenteDeValidation,
                       "Toutes les interventions sont validées",
                       "1 intervention à valider",
                       "{count} interventions à valider",
                    ),
                    onClick: () => navigate(`/interventions/renforts`),
                 }
               : null,
            user?.isGestionnaire
               ? {
                    key: "divider-profils",
                    type: "divider",
                 }
               : null,
            user?.isGestionnaire
               ? {
                    key: "notifications-profils",
                    icon:
                       stats?.nbBeneficiairesIncomplets === 0 ? (
                          <CheckCircleFilled />
                       ) : (
                          <WarningFilled />
                       ),
                    className:
                       stats?.nbBeneficiairesIncomplets === 0 ? "text-success" : "text-warning",
                    label: getLibelleByCount(
                       stats?.nbBeneficiairesIncomplets,
                       "Tous les profils des bénéficiaires sont renseignés",
                       "1 bénéficiaire avec profil à renseigner",
                       "{count} bénéficiaires avec profil à renseigner",
                    ),
                    onClick: () =>
                       navigate(
                          `/beneficiaires?filtreType=profil&filtreValeur=${
                             BENEFICIAIRE_PROFIL_A_DETERMINER
                          }`,
                       ),
                 }
               : null,
            user?.isGestionnaire
               ? {
                    key: "notifications-avisEse",
                    icon:
                       stats?.nbAvisEseEnAttente === 0 ? <CheckCircleFilled /> : <WarningFilled />,
                    className: stats?.nbAvisEseEnAttente === 0 ? "text-success" : "text-warning",
                    label: getLibelleByCount(
                       stats?.nbAvisEseEnAttente,
                       `Tous les avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} attendus sont renseignés`,
                       `1 bénéficiaire avec avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} attendu`,
                       `{count} bénéficiaires avec avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} attendu`,
                    ),
                    onClick: () =>
                       navigate(
                          `/beneficiaires?filtreType=etatAvisEse&filtreValeur=${
                             EtatAvisEse.ETAT_EN_ATTENTE
                          }`,
                       ),
                 }
               : null,
            user?.isGestionnaire
               ? {
                    key: "notifications-decision-valider",
                    icon:
                       stats?.nbDecisionsAttenteValidation === 0 ? (
                          <CheckCircleFilled />
                       ) : (
                          <WarningFilled />
                       ),
                    className:
                       stats?.nbDecisionsAttenteValidation === 0 ? "text-success" : "text-warning",
                    label: getLibelleByCount(
                       stats?.nbDecisionsAttenteValidation,
                       "Toutes les décisions d'étab. sont validées",
                       "1 décision d'étab. à valider",
                       "{count} décisions d'étab. à valider",
                    ),
                    onClick: () =>
                       navigate(
                          `/beneficiaires?filtreType=etatDecisionAmenagement&filtreValeur=${
                             EtatDecisionEtablissement.ATTENTE_VALIDATION_CAS
                          }`,
                       ),
                 }
               : null,
            user?.isAdmin
               ? {
                    key: "notifications-decision-editer",
                    icon:
                       stats?.nbDecisionsAEditer === 0 ? <CheckCircleFilled /> : <WarningFilled />,
                    className: stats?.nbDecisionsAEditer === 0 ? "text-success" : "text-warning",
                    label: getLibelleByCount(
                       stats?.nbDecisionsAEditer,
                       "Toutes les décisions d'étab. sont éditées",
                       "1 décision d'étab. à éditer",
                       "{count} décisions d'étab. à éditer",
                    ),
                    onClick: () =>
                       navigate(
                          `/beneficiaires?filtreType=etatDecisionAmenagement&filtreValeur=${
                             EtatDecisionEtablissement.VALIDE
                          }`,
                       ),
                 }
               : null,
         ],
      },
   ];
}
