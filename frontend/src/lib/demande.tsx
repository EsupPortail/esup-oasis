/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React from "react";
import {
  CheckCircleOutlined,
  CloseCircleOutlined,
  EditOutlined,
  FileDoneOutlined,
  FileExclamationOutlined,
  HourglassOutlined,
  PaperClipOutlined,
} from "@ant-design/icons";
import { env } from "@/env";
import { service } from "./service";

export interface EtatInfo {
  id: string;
  description: string;
  etape: string;
  icone: React.ReactElement;
  ordre: number;
  color: string | undefined;
  conformite?: boolean;
  gestionnairePeutModifier: boolean;
  afficherMessageSuiviEmail: boolean;
  afficherDerniereModif: boolean;
  etapeIndex: number;
  hexColor?: string;
}

export const ETAT_DEMANDE_EN_COURS = `${env.REACT_APP_API_PREFIX}/etats_demandes/1`;
export const ETAT_DEMANDE_RECEPTIONNEE = `${env.REACT_APP_API_PREFIX}/etats_demandes/2`;
export const ETAT_DEMANDE_CONFORME = `${env.REACT_APP_API_PREFIX}/etats_demandes/3`;
export const ETAT_DEMANDE_NON_CONFORME = `${env.REACT_APP_API_PREFIX}/etats_demandes/8`;
export const ETAT_DEMANDE_ATTENTE_COMMISSION = `${env.REACT_APP_API_PREFIX}/etats_demandes/7`;
export const ETAT_DEMANDE_PROFIL_ACCEPTE = `${env.REACT_APP_API_PREFIX}/etats_demandes/6`;
export const ETAT_DEMANDE_VALIDEE = `${env.REACT_APP_API_PREFIX}/etats_demandes/4`;
export const ETAT_DEMANDE_REFUSEE = `${env.REACT_APP_API_PREFIX}/etats_demandes/5`;
export const ETAT_ATTENTE_CHARTES = `${env.REACT_APP_API_PREFIX}/etats_demandes/9`;
export const ETAT_ATTENTE_ACCOMPAGNEMENT = `${env.REACT_APP_API_PREFIX}/etats_demandes/10`;

export const ETATS_DEMANDES: EtatInfo[] = [
  {
    id: ETAT_DEMANDE_EN_COURS,
    etape: "A",
    description: "{Article} demande est en cours de saisie, elle n'a pas été envoyée {au-service}.",
    icone: <EditOutlined aria-hidden />,
    ordre: 0,
    color: undefined,
    hexColor: "#999",
    conformite: undefined,
    gestionnairePeutModifier: true,
    afficherMessageSuiviEmail: false,
    afficherDerniereModif: false,
    etapeIndex: 0,
  },
  {
    id: ETAT_DEMANDE_RECEPTIONNEE,
    etape: "A",
    description: "{Article} demande a été reçue par {le-service}, elle est en cours de traitement.",
    icone: <HourglassOutlined aria-hidden />,
    ordre: 10,
    color: "purple",
    hexColor: "#531dab",
    conformite: undefined,
    gestionnairePeutModifier: true,
    afficherMessageSuiviEmail: true,
    afficherDerniereModif: false,
    etapeIndex: 1,
  },
  {
    id: ETAT_DEMANDE_CONFORME,
    etape: "B",
    description:
      "Les informations de {article} demande sont conformes, elle sera bientôt examinée.",
    icone: <FileDoneOutlined aria-hidden />,
    ordre: 20,
    color: "blue",
    hexColor: "#0958d9",
    conformite: true,
    gestionnairePeutModifier: true,
    afficherMessageSuiviEmail: true,
    afficherDerniereModif: false,
    etapeIndex: 2,
  },
  {
    id: ETAT_DEMANDE_NON_CONFORME,
    etape: "B",
    description:
      "Les informations de {article} demande ne sont pas conformes. Vous devez corriger votre demande.",
    icone: <FileExclamationOutlined aria-hidden />,
    ordre: 21,
    color: "volcano",
    hexColor: "#d4380d",
    conformite: false,
    gestionnairePeutModifier: true,
    afficherMessageSuiviEmail: false,
    afficherDerniereModif: true,
    etapeIndex: 2,
  },
  {
    id: ETAT_DEMANDE_ATTENTE_COMMISSION,
    etape: "C",
    description: "{Article} demande est en cours d'examen par la commission.",
    icone: <PaperClipOutlined aria-hidden />,
    ordre: 30,
    color: "blue",
    hexColor: "#007CBD",
    conformite: true,
    gestionnairePeutModifier: false,
    afficherMessageSuiviEmail: true,
    afficherDerniereModif: false,
    etapeIndex: 3,
  },

  {
    id: ETAT_ATTENTE_CHARTES,
    etape: "D",
    description: "L'acceptation de la charte est nécessaire pour valider {article} demande.",
    icone: <HourglassOutlined aria-hidden />,
    ordre: 40,
    color: "orange",
    hexColor: "#f0cf6c",
    conformite: true,
    gestionnairePeutModifier: false,
    afficherMessageSuiviEmail: false,
    afficherDerniereModif: false,
    etapeIndex: 3,
  },

  {
    id: ETAT_DEMANDE_PROFIL_ACCEPTE,
    etape: "D",
    description:
      "{Article} demande de profil a été acceptée, des instructions {contexte}ont été transmises par email.",
    icone: <PaperClipOutlined aria-hidden />,
    ordre: 42,
    color: "green",
    hexColor: "#8fcb77",
    conformite: true,
    gestionnairePeutModifier: false,
    afficherMessageSuiviEmail: true,
    afficherDerniereModif: false,
    etapeIndex: 3,
  },

  {
    id: ETAT_ATTENTE_ACCOMPAGNEMENT,
    etape: "D",
    description: `${service.Defini} est en train de mettre en place l'accompagnement associé à votre demande.`,
    icone: <HourglassOutlined aria-hidden />,
    ordre: 45,
    color: "orange",
    hexColor: "#d46b08",
    conformite: true,
    gestionnairePeutModifier: true,
    afficherMessageSuiviEmail: true,
    afficherDerniereModif: false,
    etapeIndex: 4,
  },

  {
    id: ETAT_DEMANDE_VALIDEE,
    etape: "D",
    description:
      "{Article} demande a été acceptée, des instructions {contexte}ont été transmises par email.",
    icone: <CheckCircleOutlined aria-hidden />,
    ordre: 50,
    color: "green",
    hexColor: "#389e0d",
    conformite: true,
    gestionnairePeutModifier: false,
    afficherMessageSuiviEmail: false,
    afficherDerniereModif: false,
    etapeIndex: 4,
  },
  {
    id: ETAT_DEMANDE_REFUSEE,
    etape: "D",
    description:
      "{Article} demande a été refusée, un email contenant plus d'informations {contexte}a été adressé.",
    icone: <CloseCircleOutlined aria-hidden />,
    ordre: 51,
    color: "red",
    hexColor: "#cf1322",
    conformite: false,
    gestionnairePeutModifier: false,
    afficherMessageSuiviEmail: false,
    afficherDerniereModif: true,
    etapeIndex: 4,
  },
];

export type EtatDemande =
  | typeof ETAT_DEMANDE_EN_COURS
  | typeof ETAT_DEMANDE_RECEPTIONNEE
  | typeof ETAT_DEMANDE_CONFORME
  | typeof ETAT_DEMANDE_VALIDEE
  | typeof ETAT_DEMANDE_REFUSEE
  | typeof ETAT_DEMANDE_PROFIL_ACCEPTE
  | typeof ETAT_DEMANDE_ATTENTE_COMMISSION
  | typeof ETAT_DEMANDE_NON_CONFORME
  | typeof ETAT_ATTENTE_CHARTES;

// — Fonctions utilitaires
export function getEtatDemandeInfo(etat: string) {
  return ETATS_DEMANDES.find((e) => e.id === etat);
}

export function getEtatDemandeIndex(etat: string) {
  return ETATS_DEMANDES.findIndex((e) => e.id === etat);
}

export function getEtatDemandeOrdre(etat: string): number {
  return ETATS_DEMANDES.find((e) => e.id === etat)?.ordre ?? -1;
}

export function getEtatDemande(etat: string) {
  return ETATS_DEMANDES.find((e) => e.id === etat);
}
