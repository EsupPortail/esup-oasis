/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { App } from "antd";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import { BoutonDecisionEtab, EtatSignatureDecision, libellesSignature } from "./BoutonDecisionEtab";

const { mockUseGetItem, mockUseGetFullCollection, mockUsePatch } = vi.hoisted(() => ({
  mockUseGetItem: vi.fn(),
  mockUseGetFullCollection: vi.fn(),
  mockUsePatch: vi.fn(),
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetItem: mockUseGetItem,
    useGetFullCollection: mockUseGetFullCollection,
    usePatch: mockUsePatch,
  }),
}));

vi.mock("@/auth/AuthProvider", () => ({
  useAuth: () => ({ user: { isAdmin: true } }),
}));

vi.mock("@utils/apiDownloader", () => ({ default: vi.fn() }));

// le serveur indique sur la décision si la date de l'avis médical est exigée : l'interface ne la devine pas
describe("BoutonDecisionEtab", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // aucun avis santé : pas de date suggérée
    mockUseGetFullCollection.mockReturnValue({ data: { items: [] }, isFetching: false });
    mockUsePatch.mockReturnValue({ mutate: vi.fn(), isPending: false });
  });

  function rendreAvecDecision(decision: Record<string, unknown>) {
    mockUseGetItem.mockReturnValue({
      data: {
        decisionAmenagementAnneeEnCours: {
          "@id": "/utilisateurs/test@uni.fr/decisions/2026",
          etat: "ATTENTE_VALIDATION_CAS",
          ...decision,
        },
      },
      isFetching: false,
    });

    return renderWithProviders(
      <App>
        <BoutonDecisionEtab utilisateurId="test@uni.fr" />
      </App>,
    );
  }

  async function ouvrirLeMenu(): Promise<HTMLElement> {
    await userEvent.click(
      await screen.findByRole("button", { name: /Décision d'établissement en attente/ }),
    );
    return screen.findByRole("menuitem", { name: /Envoyer la Décision d'établissement/ });
  }

  it("laisse l'envoi disponible quand la date n'est pas exigée", async () => {
    // instance qui n'active rien : inchangé
    rendreAvecDecision({ dateAvisMedecinRequise: false, dateAvisMedecin: null });

    expect(await ouvrirLeMenu()).not.toHaveAttribute("aria-disabled", "true");
  });

  it("laisse l'envoi disponible quand le serveur ne se prononce pas", async () => {
    // décision sérialisée sans la propriété : on ne bloque pas par défaut
    rendreAvecDecision({ dateAvisMedecin: null });

    expect(await ouvrirLeMenu()).not.toHaveAttribute("aria-disabled", "true");
  });

  it("empêche l'envoi quand la date est exigée et manquante", async () => {
    rendreAvecDecision({ dateAvisMedecinRequise: true, dateAvisMedecin: null });

    expect(await ouvrirLeMenu()).toHaveAttribute("aria-disabled", "true");
  });

  it("empêche l'envoi quand le circuit de signature ne peut pas être déterminé", async () => {
    rendreAvecDecision({
      dateAvisMedecin: null,
      motifSignatureImpossible:
        "Aucune inscription en cours : la composante de l'étudiant est inconnue.",
    });

    expect(await ouvrirLeMenu()).toHaveAttribute("aria-disabled", "true");
  });

  it("rétablit l'envoi dès que la date est saisie", async () => {
    rendreAvecDecision({ dateAvisMedecinRequise: true, dateAvisMedecin: "2026-09-01" });

    expect(await ouvrirLeMenu()).not.toHaveAttribute("aria-disabled", "true");
  });

  it.each(["ATTENTE_VALIDATION_CAS", "VALIDE"])(
    "signale la date manquante à côté du bouton : %s",
    async (etat) => {
      rendreAvecDecision({ etat, dateAvisMedecinRequise: true, dateAvisMedecin: null });

      expect(await screen.findByText("Date manquante")).toBeInTheDocument();
    },
  );

  it("ne signale rien quand la date est saisie", async () => {
    rendreAvecDecision({ dateAvisMedecinRequise: true, dateAvisMedecin: "2026-09-01" });

    await screen.findByRole("button", { name: /Décision d'établissement en attente/ });
    expect(screen.queryByText("Date manquante")).not.toBeInTheDocument();
  });

  it("ne signale plus rien une fois la décision envoyée", async () => {
    // l'alerte ne servirait plus : la date ne conditionne que l'envoi
    rendreAvecDecision({
      etat: "EDITION_DEMANDEE",
      dateAvisMedecinRequise: true,
      dateAvisMedecin: null,
    });

    await screen.findByRole("button", { name: /en cours d'envoi/ });
    expect(screen.queryByText("Date manquante")).not.toBeInTheDocument();
  });

  it("annonce l'envoi tant que le document n'est pas déposé dans le parapheur", async () => {
    rendreAvecDecision({ etat: "EN_SIGNATURE", etatSignature: null });

    expect(
      await screen.findByRole("button", { name: /Décision d'établissement en cours d'envoi/ }),
    ).toBeInTheDocument();
  });

  it("annonce la signature une fois le document déposé", async () => {
    rendreAvecDecision({ etat: "EN_SIGNATURE", etatSignature: "EN_SIGNATURE" });

    expect(
      await screen.findByRole("button", { name: /Décision d'établissement en signature/ }),
    ).toBeInTheDocument();
  });
});

describe("libellesSignature", () => {
  it("ne change rien à une décision envoyée par e-mail", () => {
    expect(libellesSignature(null, null)).toBeNull();
    expect(libellesSignature(undefined, undefined)).toBeNull();
  });

  it("annonce la signature en cours plutôt qu'un envoi imminent", () => {
    const libelles = libellesSignature(EtatSignatureDecision.EN_SIGNATURE, null);

    expect(libelles?.bouton).toBe("Décision d'établissement en signature");
    expect(libelles?.legende).toBe(
      "La Décision d'établissement est en cours de signature électronique.",
    );
    expect(libelles?.enErreur).toBe(false);
  });

  it("indique la fraîcheur de l'état quand la dernière vérification est connue", () => {
    const libelles = libellesSignature(EtatSignatureDecision.EN_SIGNATURE, "2026-09-23T14:05:00");

    expect(libelles?.legende).toContain("Dernière vérification le 23/09/2026 à 14:05.");
  });

  it.each([
    [EtatSignatureDecision.REFUSEE, "Signature de la Décision d'établissement refusée"],
    [EtatSignatureDecision.EXPIREE, "Signature de la Décision d'établissement interrompue"],
    [EtatSignatureDecision.ERREUR, "Erreur de signature de la Décision d'établissement"],
  ])("signale l'issue négative %s", (etat, bouton) => {
    const libelles = libellesSignature(etat, null);

    expect(libelles?.bouton).toBe(bouton);
    expect(libelles?.enErreur).toBe(true);
  });

  it("accorde le libellé d'un document remplacé avec le nom de la décision", () => {
    expect(libellesSignature(EtatSignatureDecision.REMPLACEE, null)?.bouton).toBe(
      "Décision d'établissement remplacée",
    );
  });
});
