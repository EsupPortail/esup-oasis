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
import { BoutonDecisionEtab } from "./BoutonDecisionEtab";

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

  it("rétablit l'envoi dès que la date est saisie", async () => {
    rendreAvecDecision({ dateAvisMedecinRequise: true, dateAvisMedecin: "2026-09-01" });

    expect(await ouvrirLeMenu()).not.toHaveAttribute("aria-disabled", "true");
  });
});
