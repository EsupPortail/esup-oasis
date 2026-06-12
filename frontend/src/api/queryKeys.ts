/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

/**
 * Clés de requête React Query centralisées.
 *
 * Ces constantes sont utilisées dans `invalidationQueryKeys` (mutations) et
 * `queryClient.invalidateQueries({ queryKey: [...] })` pour invalider le cache.
 *
 * Le mécanisme d'invalidation utilise `String.startsWith` sur `queryKey[0]`,
 * donc une clé comme `QK_UTILISATEURS` invalide toutes les requêtes dont
 * le path template commence par `/utilisateurs` (collection ET sous-ressources).
 *
 * Convention : préfixer par `QK_`, nommer d'après le path template sans slashes.
 */

// --- Événements ---
export const QK_EVENEMENTS = "/evenements";
export const QK_STATISTIQUES_EVENEMENTS = "/statistiques_evenements";
export const QK_TYPES_EVENEMENTS = "/types_evenements";

// --- Utilisateurs ---
export const QK_UTILISATEURS = "/utilisateurs";
/**
 * Invalide les clés dont un segment de chemin commence par le template `/utilisateurs/{uid}` :
 * l'item utilisateur (mis en cache avec ce template) et ses sous-ressources. Le matching est par
 * préfixe de chaîne, placeholder littéral compris : ne cible pas un `uid` concret.
 */
export const QK_UTILISATEURS_ITEM = "/utilisateurs/{uid}";
export const QK_UTILISATEURS_AMENAGEMENTS = "/utilisateurs/{uid}/amenagements";
export const QK_UTILISATEURS_AVIS_ESE = "/utilisateurs/{uid}/avis_ese";
export const QK_UTILISATEURS_AVIS_ESE_ITEM = "/utilisateurs/{uid}/avis_ese/{id}";
export const QK_UTILISATEURS_CHARTES = "/utilisateurs/{uid}/chartes";
export const QK_UTILISATEURS_DECISIONS = "/utilisateurs/{uid}/decisions/{annee}";
export const QK_UTILISATEURS_DEMANDES = "/utilisateurs/{uid}/demandes";
export const QK_UTILISATEURS_ENTRETIENS = "/utilisateurs/{uid}/entretiens";
export const QK_UTILISATEURS_PARAMETRES_UI = "/utilisateurs/{uid}/parametres_ui";
export const QK_UTILISATEURS_TAGS = "/utilisateurs/{uid}/tags";

// --- Bénéficiaires ---
export const QK_BENEFICIAIRES = "/beneficiaires";
export const QK_BENEFICIAIRES_PIECES_JOINTES = "/beneficiaires/{uid}/pieces_jointes";

// --- Demandes ---
export const QK_DEMANDES = "/demandes";
export const QK_TYPES_DEMANDES = "/types_demandes";
export const QK_TYPES_DEMANDES_CAMPAGNES = "/types_demandes/{typeId}/campagnes/{id}";

// --- Aménagements ---
export const QK_AMENAGEMENTS = "/amenagements";
export const QK_AMENAGEMENTS_UTILISATEURS = "/amenagements/utilisateurs";
export const QK_CATEGORIES_AMENAGEMENTS = "/categories_amenagements";
export const QK_TYPES_AMENAGEMENTS = "/types_amenagements";

// --- Intervenants ---
export const QK_INTERVENANTS = "/intervenants";
export const QK_INTERVENTIONS_FORFAIT = "/interventions_forfait";

// --- Référentiel ---
export const QK_CHARTES = "/chartes";
export const QK_CLUBS_SPORTIFS = "/clubs_sportifs";
export const QK_COMMISSIONS = "/commissions";
export const QK_COMMISSIONS_MEMBRES = "/commissions/{commissionId}/membres";
export const QK_COMPOSANTES = "/composantes";
export const QK_PARAMETRES = "/parametres";
export const QK_PERIODES = "/periodes";
export const QK_PROFILS = "/profils";
export const QK_ROLES_UTILISATEURS = "/roles/{roleId}/utilisateurs";
export const QK_SPORTIFS_HAUT_NIVEAU = "/sportifs_haut_niveau";
export const QK_SUIVIS_ACTIVITE = "/suivis/activite";
export const QK_TAGS = "/tags";
export const QK_CATEGORIES_TAGS = "/categories_tags";
export const QK_CATEGORIES_TAGS_ITEMS = "/categories_tags/{id}/tags";
