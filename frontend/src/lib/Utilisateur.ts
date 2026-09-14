/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IUtilisateur, Path } from "@api";
import { service } from "./service";

export enum RoleValues {
  ROLE_BENEFICIAIRE = "ROLE_BENEFICIAIRE",
  ROLE_INTERVENANT = "ROLE_INTERVENANT",
  ROLE_GESTIONNAIRE = "ROLE_GESTIONNAIRE",
  ROLE_RENFORT = "ROLE_RENFORT",
  ROLE_ADMIN = "ROLE_ADMIN",
  ROLE_ADMIN_TECHNIQUE = "ROLE_ADMIN_TECHNIQUE",
  ROLE_PLANIFICATEUR = "ROLE_PLANIFICATEUR",
  ROLE_REFERENT_COMPOSANTE = "ROLE_REFERENT_COMPOSANTE",
  ROLE_ENSEIGNANT = "ROLE_ENSEIGNANT",
  ROLE_DEMANDEUR = "ROLE_DEMANDEUR",
  ROLE_MEMBRE_COMMISSION = "ROLE_MEMBRE_COMMISSION",
  ROLE_ATTRIBUER_PROFIL = "ROLE_ATTRIBUER_PROFIL",
  ROLE_VALIDER_CONFORMITE_DEMANDE = "ROLE_VALIDER_CONFORMITE_DEMANDE",
  ROLE_USER = "ROLE_USER",
}

export type Role = keyof typeof RoleValues;
export type RoleApi = Exclude<keyof typeof RoleValues, "ROLE_ENSEIGNANT">;

export const ROLES_PLANIFICATEURS = [
  {
    value: RoleValues.ROLE_PLANIFICATEUR,
    label: "Gestionnaire",
  },
  {
    value: RoleValues.ROLE_GESTIONNAIRE,
    label: "Chargé•e d'accompagnement",
  },
  {
    value: RoleValues.ROLE_RENFORT,
    label: "Renfort",
  },
  {
    value: RoleValues.ROLE_ADMIN,
    label: "Administrateur",
  },
  {
    value: RoleValues.ROLE_ADMIN_TECHNIQUE,
    label: "Administrateur tech.",
  },
];

export const ROLES = [
  ...ROLES_PLANIFICATEURS,
  {
    value: RoleValues.ROLE_BENEFICIAIRE,
    label: "Bénéficiaire",
  },
  {
    value: RoleValues.ROLE_INTERVENANT,
    label: "Intervenant",
  },
  {
    value: RoleValues.ROLE_ENSEIGNANT,
    label: "Enseignant",
  },
  {
    value: RoleValues.ROLE_DEMANDEUR,
    label: "Demandeur",
  },
  {
    value: RoleValues.ROLE_MEMBRE_COMMISSION,
    label: "Membre de commission",
  },
  {
    value: RoleValues.ROLE_REFERENT_COMPOSANTE,
    label: `Référent•e ${service.sigle}`,
  },
];

export const ROLE_INCONNU = "-Rôle inconnu-";

export function APIEndpointByRole(role: RoleValues): Path {
  switch (role) {
    case RoleValues.ROLE_BENEFICIAIRE:
      return "/beneficiaires";
    case RoleValues.ROLE_INTERVENANT:
      return "/intervenants";
    default:
      return "/utilisateurs";
  }
}

export function getRoleLabel(role: RoleValues | undefined): string {
  if (role === undefined) return ROLE_INCONNU;
  return ROLES.find((r) => r.value === role)?.label || "-Rôle inconnu-";
}

export interface IUtilisateurBase {
  "@id"?: string;
  nom?: string;
  prenom?: string;
  email?: string;
  uid?: string;
}

export const ROLES_SELECT = [
  {
    label: getRoleLabel(RoleValues.ROLE_ADMIN),
    value: RoleValues.ROLE_ADMIN,
  },
  {
    label: getRoleLabel(RoleValues.ROLE_GESTIONNAIRE),
    value: RoleValues.ROLE_GESTIONNAIRE,
  },
  {
    label: getRoleLabel(RoleValues.ROLE_RENFORT),
    value: RoleValues.ROLE_RENFORT,
  },
];

export class Utilisateur implements IUtilisateur {
  "@id": string;

  "@type": "Utilisateur";

  uid?: string;

  nom?: string;

  prenom?: string;

  email?: string;

  telPerso?: string | null;

  emailPerso?: string | null;

  roles: RoleApi[] = [];

  renfort?: boolean;

  services?: string[] = [];

  profils?: string[] = [];

  typesEvenements?: string[] = [];

  campus?: string[] = [];

  competences?: string[] = [];

  intervenantDebut?: string | null;

  intervenantFin?: string | null;

  gestionnairesActifs?: string[] = [];

  abonneRecapHebdo?: boolean;

  abonneAvantVeille?: boolean;

  abonneVeille?: boolean;

  abonneImmediat?: boolean;

  constructor(object: IUtilisateur | Utilisateur) {
    this["@id"] = object["@id"];
    this.uid = object.uid;
    this.nom = object.nom;
    this.prenom = object.prenom;
    this.email = object.email;
    this.roles = (object.roles as RoleApi[]) || [];
    this.profils = object.profils;
    this.typesEvenements = object.typesEvenements;
    this.competences = object.competences;
    this.campus = object.campus;
    this.services = object.services;
    this.telPerso = object.telPerso;
    this.emailPerso = object.emailPerso;
    this.intervenantDebut = object.intervenantDebut;
    this.intervenantFin = object.intervenantFin;
    this.gestionnairesActifs = object.gestionnairesActifs;
    this.abonneRecapHebdo = object.abonneRecapHebdo;
    this.abonneAvantVeille = object.abonneAvantVeille;
    this.abonneVeille = object.abonneVeille;
    this.abonneImmediat = object.abonneImmediat;
  }

  get isAdmin(): boolean {
    return this.hasRole(RoleValues.ROLE_ADMIN) || this.hasRole(RoleValues.ROLE_ADMIN_TECHNIQUE);
  }

  get isAdminTechnique(): boolean {
    return this.hasRole(RoleValues.ROLE_ADMIN_TECHNIQUE);
  }

  get isGestionnaire(): boolean {
    return this.hasRole(RoleValues.ROLE_GESTIONNAIRE) || this.isAdmin;
  }

  get isRenfort(): boolean {
    return this.hasRole(RoleValues.ROLE_RENFORT);
  }

  get isPlanificateur(): boolean {
    return this.isGestionnaire || this.isRenfort;
  }

  get isIntervenant(): boolean {
    return this.hasRole(RoleValues.ROLE_INTERVENANT);
  }

  get isIntervenantOuRenfort(): boolean {
    return this.isIntervenant || this.isRenfort;
  }

  get isBeneficiaire(): boolean {
    return this.hasRole(RoleValues.ROLE_BENEFICIAIRE);
  }

  get isDemandeur(): boolean {
    return this.hasRole(RoleValues.ROLE_DEMANDEUR);
  }

  get isCommissionMembre(): boolean {
    return this.hasRole(RoleValues.ROLE_MEMBRE_COMMISSION);
  }

  get isReferentComposante(): boolean {
    return this.hasRole(RoleValues.ROLE_REFERENT_COMPOSANTE);
  }

  get roleCalcule(): RoleValues | undefined {
    if (this.isAdminTechnique) {
      return RoleValues.ROLE_ADMIN_TECHNIQUE;
    } else if (this.isAdmin) {
      return RoleValues.ROLE_ADMIN;
    } else if (this.isRenfort) {
      return RoleValues.ROLE_RENFORT;
    } else if (this.isGestionnaire) {
      return RoleValues.ROLE_GESTIONNAIRE;
    } else if (this.isCommissionMembre) {
      return RoleValues.ROLE_MEMBRE_COMMISSION;
    } else if (this.isReferentComposante) {
      return RoleValues.ROLE_REFERENT_COMPOSANTE;
    } else if (this.isIntervenant) {
      return RoleValues.ROLE_INTERVENANT;
    } else if (this.isBeneficiaire) {
      return RoleValues.ROLE_BENEFICIAIRE;
    } else if (this.isDemandeur) {
      return RoleValues.ROLE_DEMANDEUR;
    }
    return undefined;
  }

  public hasRole(role: RoleValues): boolean {
    return this.roles.includes(role as RoleApi);
  }

  public getRoleColor(): string {
    if (this.isAdmin) {
      return "gold";
    } else if (this.isGestionnaire) {
      return "blue";
    } else if (this.isRenfort) {
      return "processing";
    } else if (this.isCommissionMembre) {
      return "purple";
    }
    return "default";
  }
}
