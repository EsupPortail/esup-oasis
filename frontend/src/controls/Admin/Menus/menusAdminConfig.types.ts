/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

/** Un libellé de menu MENU_*, avec ses éventuels sous-items. */
export interface MenuLabelNode {
  /** Clé du paramètre en base, ex. "MENU_PLANNING_PLANIF". */
  cle: string;
  /** Libellé par défaut en dur dans le menuItemXxx correspondant, utilisé si le paramètre n'a pas
   * (encore) de valeur courante en base — évite d'afficher "Non configuré" alors que le menu réel
   * afficherait bien ce texte via le fallback `labels?.MENU_XXX ?? "..."`. */
  defaut: string;
  /** Condition d'affichage réelle dans le menu, à titre informatif pour l'admin. */
  condition?: string;
  enfants?: MenuLabelNode[];
}

/** Rôle applicatif utilisé pour simuler un utilisateur dans l'aperçu du menu (cf. mockUtilisateurs.ts). */
export type RoleOngletMenu =
  | "gestionnaire"
  | "renfort"
  | "demandeur"
  | "beneficiaire"
  | "intervenant"
  | "commission"
  | "referent"
  | "administrateur"
  | "commun";

export interface OngletMenuConfig {
  key: string;
  label: string;
  role: RoleOngletMenu;
  items: MenuLabelNode[];
  /** Couleur d'identification du rôle (var CSS ou hex), reprise du système de couleurs de rôle existant. */
  accent: string;
}
