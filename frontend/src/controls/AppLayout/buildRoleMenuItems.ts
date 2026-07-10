/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { NavigateFunction } from "react-router-dom";
import { MenuProps } from "antd";
import { Utilisateur } from "@lib";
import { menuItemDemandeurs } from "@controls/AppLayout/menuItems/MenuItemDemandeurs";
import { menuItemBeneficiaires } from "@controls/AppLayout/menuItems/MenuItemBeneficiaires";
import { menuItemIntervenants } from "@controls/AppLayout/menuItems/MenuItemIntervenants";
import { menuItemPlanningPlanificateur } from "@controls/AppLayout/menuItems/MenuItemPlanningPlanificateur";
import { menuItemDemandeur } from "@controls/AppLayout/menuItems/MenuItemDemandeur";
import { menuItemPlanningBeneficiaireIntervenant } from "@controls/AppLayout/menuItems/MenuItemPlanningBeneficiaireIntervenant";
import { menuItemServicesFaitsIntervenant } from "@controls/AppLayout/menuItems/MenuItemServicesFaitsIntervenant";
import { menuItemDemandesForMembresCommission } from "@controls/AppLayout/menuItems/MenuItemDemandesForMembresCommission";
import { menuItemAmenagementsForReferents } from "@controls/AppLayout/menuItems/MenuItemAmenagementsForReferents";

/**
 * Compose les items de menu propres au(x) rôle(s) métier de l'utilisateur (Planificateur,
 * Demandeur, Bénéficiaire, Intervenant, Membre de commission, Référent de composante).
 *
 * Seule source de vérité pour cette composition : réutilisée à l'identique par le menu réel
 * de production (`AppLayoutMenu.tsx`) et par l'aperçu de la page d'administration
 * (`MenuPreview.tsx`), pour qu'ils ne puissent pas diverger silencieusement.
 */
export function buildRoleMenuItems(
  user: Utilisateur,
  navigate: NavigateFunction,
  setSelectedKey: (key: string) => void,
  labels?: Record<string, string>,
): MenuProps["items"] {
  const items: MenuProps["items"] = [];

  if (user.isPlanificateur) {
    items.push(...(menuItemDemandeurs(setSelectedKey, navigate, labels) || []));
    items.push(...(menuItemBeneficiaires(setSelectedKey, navigate, user, labels) || []));
    items.push(...(menuItemIntervenants(setSelectedKey, navigate, labels) || []));
    items.push(...(menuItemPlanningPlanificateur(setSelectedKey, user, navigate, labels) || []));
  }

  if (user.isDemandeur) {
    items.push(
      ...(menuItemDemandeur(
        setSelectedKey,
        navigate,
        user.isBeneficiaire || user.isIntervenant ? "" : "mr-auto",
        labels,
      ) || []),
    );
  }

  if (user.isBeneficiaire && !user.isPlanificateur) {
    items.push(
      ...(menuItemPlanningBeneficiaireIntervenant(setSelectedKey, navigate, "mr-auto", labels) ||
        []),
    );
  } else if (user.isIntervenant && !user.isPlanificateur) {
    items.push(
      ...(menuItemPlanningBeneficiaireIntervenant(setSelectedKey, navigate, undefined, labels) ||
        []),
    );
    items.push(
      ...(menuItemServicesFaitsIntervenant(setSelectedKey, navigate, "mr-auto", labels) || []),
    );
  }

  if (user.isCommissionMembre && !user.isPlanificateur) {
    items.push(...(menuItemDemandesForMembresCommission(setSelectedKey, navigate, labels) || []));
  }

  if (user.isReferentComposante && !user.isPlanificateur) {
    items.push(...(menuItemAmenagementsForReferents(setSelectedKey, navigate, labels) || []));
  }

  return items;
}
