/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { RoleValues } from "@lib";
import { env } from "@/env";

/**
 * Version de l'application.
 *
 * @interface IVersion
 * @property {string} version - The version number of the application.
 * @property {string} date - The release date of the version.
 * @property {string} description - A brief description of the version.
 * @property {Object[]} changes - An array of changes included in this version.
 * @property {string} changes.description - Description of a specific change.
 * @property {boolean} [changes.important] - Indicates if the change is critical or important.
 * @property {RoleValues[]} [changes.roles] - Roles affected or related to the change.
 * @property {"add" | "change" | "fix" | "remove" | "to-do"} [changes.type] - Type of change, indicating whether it is an addition, modification, fix, removal, or to-do item.
 */
export interface IVersion {
  version: string;
  description: string;
  changes?: {
    description: string;
    important?: boolean;
    roles?: RoleValues[];
    type?: "add" | "change" | "fix" | "remove" | "to-do";
  }[];
}

/**
 * Changelog de l'application.
 * Liste des versions et des changements associés.
 */
export const VERSIONS: IVersion[] = [
  {
    version: "2.4.1",
    description: "Personnalisation du menu",
    changes: [
      {
        description:
          "L'administrateur peut personnaliser les libellés du menu principal de l'application",
        type: "add",
      },
    ],
  },
  {
    version: "2.4.0",
    description: "Amélioration des performances (frontend)",
    changes: [
      {
        description: "Amélioration globale des performances de l'application 🚀",
        type: "fix",
      },
      {
        description: "Mémorisation des filtres durant la navigation",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
      {
        description: "Dépôt de plusieurs fichiers simultanément lors d'une demande",
        roles: [RoleValues.ROLE_DEMANDEUR, RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
      {
        description: "Consultation des campagnes passées pour les types de demande",
        roles: [RoleValues.ROLE_ADMIN],
        type: "add",
      },
      {
        description: "Exportations par lots pour éviter les erreurs de chargement",
        roles: [RoleValues.ROLE_ADMIN, RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
      {
        description:
          "Modification des informations personnelles d'un bénéficiaire (mail, téléphone)",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "fix",
      },
      {
        description: "Ajout d'une demande depuis le profil d'un bénéficiaire",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
      {
        description: "Filtre sur la date de validité dans le profil bénéficiaire",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
    ],
  },
  {
    version: "2.3.0",
    description: "Amélioration des performances (backend)",
    changes: [
      {
        description: "Amélioration globale des performances de l'application 🚀",
        type: "fix",
      },
      {
        description: "Amélioration de la gestion du cache",
        type: "fix",
      },
      {
        description:
          "Correction de l'affichage du montant d'une intervention (modale intervention)",
        type: "fix",
      },
      {
        description: "Correction du menu Accessibilité sur mobile",
        type: "fix",
      },
      {
        description: "Amélioration de l'utilisation des aménagements par bénéficiaires",
        type: "add",
      },
      {
        description:
          "Ajout d'un message si le navigateur n'est pas configuré sur la timezone de Paris",
        type: "add",
      },
    ],
  },
  {
    version: "2.2.5",
    description: "Améliorations des performances",
    changes: [
      {
        description:
          "Amélioration globale des performances, notamment sur les statistiques du Tableau de bord",
        type: "fix",
      },
      {
        description: "Amélioration du cache",
        type: "fix",
      },
    ],
  },
  {
    version: "2.2.4",
    description: "Bugfix et améliorations mineures",
    changes: [
      {
        description: "Correction du tri intervenants services faits",
        type: "fix",
      },
    ],
  },
  {
    version: "2.2.3",
    description: "Bugfix et améliorations mineures",
    changes: [
      {
        description: "Calcul du début de l'année universitaire",
        type: "fix",
      },
    ],
  },
  {
    version: "2.2.2",
    description: "Bugfix et améliorations mineures",
    changes: [
      {
        description: "Gestion du cache lors de la création d'une nouvelle demande",
        type: "fix",
      },
    ],
  },
  {
    version: "2.2.1",
    description: "Bugfix et améliorations mineures",
    changes: [
      {
        description: "Correction d'un bug dans le bilan d'activités",
        type: "fix",
      },
    ],
  },
  {
    version: "2.2.0",
    description: "Accompagnement des bénéficiaires",
    changes: [
      {
        description: "Gestion de l'accompagnement des bénéficiaires : filtre + modification",
        type: "add",
      },
      {
        description: "Corrections de bugs mineurs et améliorations des performances",
        type: "fix",
      },
    ],
  },
  {
    version: "2.1.7",
    description: "Correctifs divers",
    changes: [
      {
        description: "Exportation des aménagements",
        type: "add",
      },
      {
        description: "Amélioration de l'exportation des aménagements par bénéficiaires",
        type: "fix",
      },
      {
        description: "Corrections de bugs mineurs",
        type: "fix",
      },
    ],
  },
  {
    version: "2.1.2",
    description: "Correctifs divers",
    changes: [
      {
        description: `Amélioration de l'accessibilité / police Lexend`,
        type: "add",
      },
      {
        description: "Filtre campagnes archivées",
        type: "add",
      },
    ],
  },
  {
    version: "2.1.0",
    description: "Version packagée pour distribution ESUP",
    changes: [
      {
        description: `(Dé)personnalisation de l'application`,
        type: "add",
      },
      {
        description: `Mise à jour de la documentation`,
        type: "add",
      },
    ],
  },
  {
    version: "2.0.1",
    description: "Bilan activité du service",
    changes: [
      {
        description: `Bilan activité du service ${env.REACT_APP_SERVICE}`,
        roles: [RoleValues.ROLE_ADMIN],
        type: "add",
      },
      {
        description: "Corrections de bugs mineurs",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "fix",
      },
    ],
  },
  {
    version: "2.0.0",
    description: "Gestion des demandes et des aménagements",
    changes: [
      {
        description: "Nouveaux rôles : membre de commission, référent",
        roles: [RoleValues.ROLE_REFERENT_COMPOSANTE, RoleValues.ROLE_MEMBRE_COMMISSION],
        type: "add",
      },
      {
        description: "Gestion des demandes",
        roles: [
          RoleValues.ROLE_GESTIONNAIRE,
          RoleValues.ROLE_RENFORT,
          RoleValues.ROLE_MEMBRE_COMMISSION,
        ],
        type: "add",
      },
      {
        description: "Gestion des aménagements",
        roles: [RoleValues.ROLE_GESTIONNAIRE, RoleValues.ROLE_RENFORT],
        type: "add",
      },
      {
        description: "Gestion des décisions d'établissement",
        roles: [RoleValues.ROLE_GESTIONNAIRE, RoleValues.ROLE_ADMIN],
        type: "add",
      },
      {
        description: "Gestion des avis Espace santé et des entretiens",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
    ],
  },
  {
    version: "1.1.0",
    description: "Bilans",
    changes: [
      {
        description: "Suivi de l'activité des bénéficiaires et des intervenants",
        roles: [RoleValues.ROLE_ADMIN, RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
    ],
  },
  {
    version: "1.0.1",
    description: "Correctifs divers  ",
  },
  {
    version: "1.0.0",
    description: "Mise en production du lot 1 🚀",
    changes: [
      {
        description: "Gestion de la planification des évènements",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
      },
      {
        description: "Gestion des interventions au forfait",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
      },
      {
        description: "Gestion des interventions des renforts",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
      },
      {
        description: "Gestion des référentiels",
        roles: [RoleValues.ROLE_ADMIN],
      },
      {
        description: "Gestion des services-faits",
        roles: [RoleValues.ROLE_ADMIN],
      },
      {
        description: "Domaine Bénéficiaire",
        roles: [RoleValues.ROLE_BENEFICIAIRE],
      },
      {
        description: "Domaine Intervenant",
        roles: [RoleValues.ROLE_INTERVENANT],
      },
    ],
  },
  {
    version: "0.4.0",
    description: "Domaine Bénéficiaire",
    changes: [
      {
        description: "Consultation des évènements",
        roles: [RoleValues.ROLE_BENEFICIAIRE],
      },
      {
        description: "Compte Bénéficiaire",
        roles: [RoleValues.ROLE_BENEFICIAIRE],
      },
    ],
  },
  {
    version: "0.3.0",
    description: "Domaine Intervenant",
    changes: [
      {
        description: "Dashboard",
        roles: [RoleValues.ROLE_INTERVENANT],
      },
      {
        description: "Consultation des évènements",
        roles: [RoleValues.ROLE_INTERVENANT],
      },
      {
        description: "Consultation des services faits",
        roles: [RoleValues.ROLE_INTERVENANT],
      },
      {
        description: "Compte Intervenant",
        roles: [RoleValues.ROLE_INTERVENANT],
      },
    ],
  },
  {
    version: "0.2.1",
    description: "Pré-version 2 - Correctifs",
    changes: [
      {
        description: "Corrections de bugs mineurs",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "fix",
      },
      {
        description: "Saisie en lot des interventions au forfait",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
    ],
  },
  {
    version: "0.2.0",
    description: "Pré-version 2",
    changes: [
      {
        description: "Gestion des interventions au forfait",
        roles: [RoleValues.ROLE_GESTIONNAIRE, RoleValues.ROLE_ADMIN],
        type: "add",
      },
      {
        description: "Validation des interventions des renforts",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
    ],
  },
  {
    version: "0.1.1",
    description: "Pré-version 1 - Correctifs",
    changes: [
      {
        description: "Corrections de bugs mineurs",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "fix",
      },
    ],
  },
  {
    version: "0.1.0",
    description: "Pré-version 1",
    changes: [
      {
        description: "Gestion de la planification des évènements",
        roles: [RoleValues.ROLE_GESTIONNAIRE],
        type: "add",
      },
      {
        description: "Gestion de la configuration de l'application",
        roles: [RoleValues.ROLE_ADMIN],
        type: "add",
      },
    ],
  },
];
