/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { env } from "@/env";
import { IUtilisateur } from "@api";
import { decisionEtab } from "./decisionEtab";

/**
 * Vrai quand un parapheur électronique est configuré : `REACT_APP_PARAPHEUR` reflète la variable
 * backend `PARAPHEUR`. Les réglages qui n'ont de sens qu'avec un parapheur, comme le circuit de
 * signature d'une composante, ne sont affichés que dans ce cas.
 */
export function parapheurConfigure(): boolean {
  return !!env.REACT_APP_PARAPHEUR?.trim();
}

// relecture du bénéficiaire pendant l'envoi : la fin du dépôt s'affiche sans recharger la page
export const INTERVALLE_SUIVI_ENVOI = 5000;

/** Intervalle de relecture d'un bénéficiaire dont la décision est en cours d'envoi, `false` sinon. */
export function intervalleSuiviEnvoi(utilisateur?: IUtilisateur): number | false {
  return parapheurConfigure() &&
    utilisateur?.decisionAmenagementAnneeEnCours?.etat === "EDITION_DEMANDEE"
    ? INTERVALLE_SUIVI_ENVOI
    : false;
}

/**
 * Motif à afficher sur les actions bloquées quand la décision de l'année est en cours d'envoi, dans
 * le circuit de signature ou refusée sans avoir été reprise, `undefined` sinon. Le serveur refuse les
 * modifications en signature ou après un refus : l'interface les désactive pour l'annoncer avant la
 * saisie, et attend la fin de l'envoi, qui ne dure que le temps du dépôt.
 */
export function verrouSignature(utilisateur?: IUtilisateur): string | undefined {
  switch (utilisateur?.decisionAmenagementAnneeEnCours?.etat) {
    case "EDITION_DEMANDEE":
      return parapheurConfigure()
        ? `${decisionEtab.Defini} est en cours d'envoi : modification possible une fois l'envoi terminé.`
        : undefined;
    case "EN_SIGNATURE":
      return `${decisionEtab.Defini} est en cours de signature électronique : modification impossible jusqu'à la fin du circuit.`;
    case "REFUSEE":
      return `${decisionEtab.Defini} a été refusé${decisionEtab.accordE} dans le circuit de signature : modification possible après la reprise ${decisionEtab.de}.`;
    default:
      return undefined;
  }
}
