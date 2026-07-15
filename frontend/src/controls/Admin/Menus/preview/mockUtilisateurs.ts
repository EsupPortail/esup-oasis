/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Utilisateur } from "@lib";
import { RoleOngletMenu } from "@controls/Admin/Menus/menusAdminConfig.types";
import { env } from "@/env";

// Rôles minimaux activant, pour chaque onglet, tous les getters dérivés (isGestionnaire,
// isRenfort...) dont dépendent les sous-items conditionnels du menu réel — l'aperçu vise à
// montrer chaque libellé éditable de l'onglet, pas à simuler un utilisateur précis.
const ROLES_PAR_ONGLET: Record<RoleOngletMenu, string[]> = {
  gestionnaire: ["ROLE_USER", "ROLE_GESTIONNAIRE"],
  renfort: ["ROLE_USER", "ROLE_RENFORT"],
  demandeur: ["ROLE_USER", "ROLE_DEMANDEUR"],
  beneficiaire: ["ROLE_USER", "ROLE_BENEFICIAIRE"],
  intervenant: ["ROLE_USER", "ROLE_INTERVENANT"],
  commission: ["ROLE_USER", "ROLE_MEMBRE_COMMISSION"],
  referent: ["ROLE_USER", "ROLE_REFERENT_COMPOSANTE"],
  // isAdmin implique isGestionnaire (cf. Utilisateur.ts) : l'aperçu montre donc aussi, à raison,
  // les items du planificateur — c'est ce que voit réellement un administrateur en production.
  administrateur: ["ROLE_USER", "ROLE_ADMIN"],
  commun: ["ROLE_USER", "ROLE_BENEFICIAIRE", "ROLE_ADMIN", "ROLE_GESTIONNAIRE"],
};

export function mockUtilisateurPourOnglet(role: RoleOngletMenu): Utilisateur {
  return new Utilisateur({
    "@id": "/utilisateurs/apercu",
    "@type": "Utilisateur",
    uid: "apercu@preview.local",
    prenom: "Utilisateur",
    nom: env.REACT_APP_TITRE,
    roles: ROLES_PAR_ONGLET[role] as never[],
  });
}
