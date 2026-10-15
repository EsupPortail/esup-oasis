/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { beforeEach, describe, expect, it, vi } from "vitest";
import { IUtilisateur } from "@api";

const { mockEnv } = vi.hoisted(() => ({
  mockEnv: { REACT_APP_PARAPHEUR: "factice" } as { REACT_APP_PARAPHEUR: string | null },
}));

vi.mock("@/env", () => ({ env: mockEnv }));

// nom par défaut de la décision, fixé ici pour ne pas dépendre de la configuration de l'environnement de test
vi.mock("./decisionEtab", () => ({
  decisionEtab: {
    Defini: "La décision d'établissement",
    de: "de la décision d'établissement",
    accordE: "e",
  },
}));

import {
  depotEnCours,
  INTERVALLE_SUIVI_ENVOI,
  intervalleSuiviEnvoi,
  verrouSignature,
} from "./parapheur";

function beneficiaire(etat?: string, etatSignature: string | null = null): IUtilisateur {
  return {
    decisionAmenagementAnneeEnCours: etat ? { etat, etatSignature } : null,
  } as IUtilisateur;
}

beforeEach(() => {
  mockEnv.REACT_APP_PARAPHEUR = "factice";
});

describe("verrouSignature", () => {
  it("ne bloque pas l'envoi par e-mail, comme sans parapheur", () => {
    expect(verrouSignature(beneficiaire("EDITION_DEMANDEE"))).toBeUndefined();
  });

  it("bloque les modifications dès la demande, dépôt compris, jusqu'à la fin du circuit", () => {
    expect(verrouSignature(beneficiaire("EN_SIGNATURE"))).toBe(
      "La décision d'établissement est en cours de signature électronique : modification impossible jusqu'à la fin du circuit.",
    );
    expect(verrouSignature(beneficiaire("EN_SIGNATURE", "EN_SIGNATURE"))).toBe(
      "La décision d'établissement est en cours de signature électronique : modification impossible jusqu'à la fin du circuit.",
    );
  });

  it("bloque les modifications d'une décision refusée tant qu'elle n'est pas reprise", () => {
    expect(verrouSignature(beneficiaire("REFUSEE"))).toBe(
      "La décision d'établissement a été refusée dans le circuit de signature : modification possible après la reprise de la décision d'établissement.",
    );
  });

  it("laisse modifier hors signature ou sans décision", () => {
    expect(verrouSignature(beneficiaire("ATTENTE_VALIDATION_CAS"))).toBeUndefined();
    expect(verrouSignature(beneficiaire("EDITE"))).toBeUndefined();
    expect(verrouSignature(beneficiaire())).toBeUndefined();
    expect(verrouSignature(undefined)).toBeUndefined();
  });
});

describe("depotEnCours", () => {
  it("vaut tant que la décision en signature n'a pas d'état rapporté par le parapheur", () => {
    expect(depotEnCours(beneficiaire("EN_SIGNATURE"))).toBe(true);
  });

  it("ne vaut plus une fois le document déposé, ni hors signature", () => {
    expect(depotEnCours(beneficiaire("EN_SIGNATURE", "EN_SIGNATURE"))).toBe(false);
    expect(depotEnCours(beneficiaire("EDITION_DEMANDEE"))).toBe(false);
    expect(depotEnCours(undefined)).toBe(false);
  });
});

describe("intervalleSuiviEnvoi", () => {
  it("relit le bénéficiaire pendant le dépôt pour afficher sa fin", () => {
    expect(intervalleSuiviEnvoi(beneficiaire("EN_SIGNATURE"))).toBe(INTERVALLE_SUIVI_ENVOI);
  });

  it("ne relit plus une fois la décision déposée, ni pour l'envoi par e-mail", () => {
    expect(intervalleSuiviEnvoi(beneficiaire("EN_SIGNATURE", "EN_SIGNATURE"))).toBe(false);
    expect(intervalleSuiviEnvoi(beneficiaire("EDITION_DEMANDEE"))).toBe(false);
    expect(intervalleSuiviEnvoi(beneficiaire("ATTENTE_VALIDATION_CAS"))).toBe(false);
    expect(intervalleSuiviEnvoi(undefined)).toBe(false);
  });
});
