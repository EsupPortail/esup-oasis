/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { App } from "antd";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import { dateAvisMedecinSuggeree, ModalDecisionObservations } from "./ModalDecisionObservations";
import { IAvisEse } from "@api";
import dayjs from "dayjs";

const { mockUseGetItem, mockUseGetFullCollection, mockMutate, mockUsePatch } = vi.hoisted(() => ({
  mockUseGetItem: vi.fn(),
  mockUseGetFullCollection: vi.fn(),
  mockMutate: vi.fn(),
  mockUsePatch: vi.fn(),
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetItem: mockUseGetItem,
    useGetFullCollection: mockUseGetFullCollection,
    usePatch: mockUsePatch,
  }),
}));

// la zone de texte des observations : l'input du DatePicker porte aussi le rôle « textbox »
async function findObservationsTextarea(): Promise<HTMLTextAreaElement> {
  const textboxes = await screen.findAllByRole("textbox");
  const textarea = textboxes.find((el): el is HTMLTextAreaElement => el.tagName === "TEXTAREA");
  if (!textarea) {
    throw new Error("Textarea 'Observations particulières' introuvable");
  }
  return textarea;
}

describe("ModalDecisionObservations", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseGetItem.mockReturnValue({
      data: {
        "@id": "/utilisateurs/test@uni.fr/decisions/2026",
        etat: "ATTENTE_VALIDATION_CAS",
        observations: "",
        dateAvisMedecin: null,
      },
      isFetching: false,
    });
    // aucun avis santé : pas de date suggérée
    mockUseGetFullCollection.mockReturnValue({ data: { items: [] }, isFetching: false });
    mockUsePatch.mockReturnValue({ mutate: mockMutate, isPending: false });
  });

  function renderModal(open = true) {
    return renderWithProviders(
      <App>
        <ModalDecisionObservations
          open={open}
          setOpen={vi.fn()}
          decisionId="/utilisateurs/test@uni.fr/decisions/2026"
          utilisateurId="test@uni.fr"
        />
      </App>,
    );
  }

  it("affiche un textarea 'Observations particulières'", async () => {
    renderModal();
    await screen.findByRole("dialog");

    const textarea = await findObservationsTextarea();
    expect(textarea).toBeInTheDocument();
    expect(textarea.tagName).toBe("TEXTAREA");
  });

  it("affiche un DatePicker 'Date de l'avis médical'", async () => {
    renderModal();
    await screen.findByRole("dialog");

    const datePickerInput = await screen.findByPlaceholderText("JJ/MM/AAAA");
    expect(datePickerInput).toBeInTheDocument();
    expect(screen.getByText("Date de l'avis médical", { selector: "label" })).toBeInTheDocument();
  });

  it("pré-remplit le DatePicker avec la date renvoyée par l'API", async () => {
    mockUseGetItem.mockReturnValue({
      data: {
        "@id": "/utilisateurs/test@uni.fr/decisions/2026",
        etat: "ATTENTE_VALIDATION_CAS",
        observations: "",
        dateAvisMedecin: "2026-06-15",
      },
      isFetching: false,
    });
    renderModal();
    await screen.findByRole("dialog");

    const datePickerInput = (await screen.findByPlaceholderText("JJ/MM/AAAA")) as HTMLInputElement;
    await waitFor(() => {
      expect(datePickerInput.value).toBe("15/06/2026");
    });
  });

  it("accepte la saisie utilisateur et la transmet via PATCH", async () => {
    const user = userEvent.setup();
    renderModal();
    await screen.findByRole("dialog");

    const textarea = await findObservationsTextarea();
    await user.click(textarea);
    await user.type(textarea, "Tiers temps validé sur dossier 2026");

    const okButton = screen.getByRole("button", { name: /enregistrer/i });
    await user.click(okButton);

    await waitFor(() => {
      expect(mockMutate).toHaveBeenCalledTimes(1);
    });
    expect(mockMutate).toHaveBeenCalledWith({
      "@id": "/utilisateurs/test@uni.fr/decisions/2026",
      data: { observations: "Tiers temps validé sur dossier 2026", dateAvisMedecin: null },
    });
  });

  it("transmet dateAvisMedecin au format ISO YYYY-MM-DD via PATCH", async () => {
    const user = userEvent.setup();
    // date fournie par l'API : la saisie clavier dans le DatePicker n'est pas reproductible en JSDOM
    mockUseGetItem.mockReturnValue({
      data: {
        "@id": "/utilisateurs/test@uni.fr/decisions/2026",
        etat: "ATTENTE_VALIDATION_CAS",
        observations: "",
        dateAvisMedecin: "2026-06-15",
      },
      isFetching: false,
    });
    renderModal();
    await screen.findByRole("dialog");

    const datePickerInput = (await screen.findByPlaceholderText("JJ/MM/AAAA")) as HTMLInputElement;
    await waitFor(() => {
      expect(datePickerInput.value).toBe("15/06/2026");
    });

    const okButton = screen.getByRole("button", { name: /enregistrer/i });
    await user.click(okButton);

    await waitFor(() => {
      expect(mockMutate).toHaveBeenCalledTimes(1);
    });
    expect(mockMutate).toHaveBeenCalledWith({
      "@id": "/utilisateurs/test@uni.fr/decisions/2026",
      data: { observations: null, dateAvisMedecin: "2026-06-15" },
    });
  });

  it("envoie null pour les deux champs quand la saisie est vide", async () => {
    const user = userEvent.setup();
    mockUseGetItem.mockReturnValue({
      data: {
        "@id": "/utilisateurs/test@uni.fr/decisions/2026",
        etat: "ATTENTE_VALIDATION_CAS",
        observations: "    ",
        dateAvisMedecin: null,
      },
      isFetching: false,
    });
    renderModal();
    await screen.findByRole("dialog");

    const textarea = await findObservationsTextarea();
    await user.clear(textarea);

    const okButton = screen.getByRole("button", { name: /enregistrer/i });
    await user.click(okButton);

    await waitFor(() => {
      expect(mockMutate).toHaveBeenCalledTimes(1);
    });
    expect(mockMutate).toHaveBeenCalledWith({
      "@id": "/utilisateurs/test@uni.fr/decisions/2026",
      data: { observations: null, dateAvisMedecin: null },
    });
  });

  // un clic écraserait les valeurs existantes par des champs vides
  it.each([
    ["décision en chargement", { data: undefined, isFetching: true }],
    ["chargement de la décision échoué", { data: undefined, isFetching: false }],
  ])("désactive l'enregistrement : %s", async (_cas, reponse) => {
    mockUseGetItem.mockReturnValue(reponse);
    renderModal();
    await screen.findByRole("dialog");

    expect(screen.getByRole("button", { name: /enregistrer/i })).toBeDisabled();
  });

  it("désactive l'enregistrement tant que les avis santé se chargent", async () => {
    mockUseGetFullCollection.mockReturnValue({ data: undefined, isFetching: true });
    renderModal();
    await screen.findByRole("dialog");

    expect(screen.getByRole("button", { name: /enregistrer/i })).toBeDisabled();
  });

  it("suggère le début de l'avis santé en cours en placeholder, sans l'enregistrer d'office", async () => {
    const debut = dayjs().subtract(1, "month");
    mockUseGetFullCollection.mockReturnValue({
      data: {
        items: [
          { debut: debut.format("YYYY-MM-DD"), fin: dayjs().add(1, "year").format("YYYY-MM-DD") },
        ],
      },
      isFetching: false,
    });
    renderModal();
    await screen.findByRole("dialog");

    const datePickerInput = (await screen.findByPlaceholderText(
      debut.format("DD/MM/YYYY"),
    )) as HTMLInputElement;
    expect(datePickerInput.value).toBe("");
    expect(screen.getByText(/Date suggérée/)).toBeInTheDocument();
  });
});

describe("dateAvisMedecinSuggeree", () => {
  const avisEnCours = {
    debut: dayjs().subtract(2, "month").format("YYYY-MM-DD"),
    fin: null,
  } as unknown as IAvisEse;
  const avisPasse = { debut: "2020-09-01", fin: "2021-08-31" } as unknown as IAvisEse;

  it("ne suggère rien quand la décision porte déjà une date", () => {
    expect(dateAvisMedecinSuggeree("2026-06-15", [avisEnCours])).toBeNull();
  });

  it("suggère le début de l'avis santé en cours, pas d'un avis terminé", () => {
    expect(dateAvisMedecinSuggeree(null, [avisPasse, avisEnCours])?.format("YYYY-MM-DD")).toBe(
      avisEnCours.debut,
    );
    expect(dateAvisMedecinSuggeree(null, [avisPasse])).toBeNull();
    expect(dateAvisMedecinSuggeree(undefined, [])).toBeNull();
  });
});
