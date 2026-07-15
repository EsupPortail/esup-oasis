/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { lazy } from "react";
import { IRoute } from "@routes/AppRoutes";
import { RoleValues } from "@lib";

export const ADMIN_ROUTES: IRoute[] = [
  {
    path: "/impersonate/:uid",
    element: lazy(() => import("@routes/commun/Impersonate")),
    roles: [RoleValues.ROLE_ADMIN, RoleValues.ROLE_ADMIN_TECHNIQUE],
  },
  {
    path: "/administration/referentiels/parametres",
    element: lazy(() => import("@routes/administration/Parametres/Parametres")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/menus",
    element: lazy(() => import("@routes/administration/Menus/Menus")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/bilans/services-faits",
    element: lazy(() => import("@routes/administration/Bilans/ServicesFaits/ServicesFaits")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/bilans/financier",
    element: lazy(() => import("@routes/administration/Bilans/BilanFinancier/BilanFinancier")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/bilans/activites",
    element: lazy(() => import("@routes/administration/Bilans/BilanActivites/BilanActivites")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/utilisateurs",
    element: lazy(() => import("@routes/administration/Utilisateurs/Utilisateurs")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/sportifs-haut-niveau",
    element: lazy(
      () => import("@routes/administration/Referentiel/SportifsHautNiveau/SportifsHautNiveau"),
    ),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/types-amenagements",
    element: lazy(() => import("@routes/administration/Referentiel/Amenagements/Amenagements")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/clubs-sportifs",
    element: lazy(() => import("@routes/administration/Referentiel/ClubsSportifs/ClubsSportifs")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/referents",
    element: lazy(() => import("@routes/administration/Referentiel/Referents/Referents")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/chartes",
    element: lazy(() => import("@routes/administration/Referentiel/Chartes/Chartes")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/periodes-rh",
    element: lazy(() => import("@routes/administration/Referentiel/PeriodeRh/PeriodesRh")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/types-evenements",
    element: lazy(() => import("@routes/administration/Referentiel/TypeEvenement/TypesEvenements")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/profils",
    element: lazy(() => import("@routes/administration/Referentiel/Profils/Profils")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/tags",
    element: lazy(() => import("@routes/administration/Referentiel/Tags/CategoriesTags")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/types_demandes",
    element: lazy(() => import("@routes/administration/TypesDemandes/TypesDemandes")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/commissions/:id",
    element: lazy(() => import("@routes/administration/Commissions/Commissions")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/commissions",
    element: lazy(() => import("@routes/administration/Commissions/Commissions")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration/referentiels/:referentielId",
    element: lazy(() => import("@routes/administration/Referentiel/Referentiel")),
    roles: [RoleValues.ROLE_ADMIN],
  },
  {
    path: "/administration",
    element: lazy(() => import("@routes/administration/Administration")),
    roles: [RoleValues.ROLE_ADMIN],
  },
];
