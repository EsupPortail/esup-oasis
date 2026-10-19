/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { screen, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { App } from "antd";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { renderWithProviders } from "@/test";
import SignatureElectronique from "./SignatureElectronique";

const { mockEnv, mockMutate } = vi.hoisted(() => ({
  mockEnv: {} as Record<string, unknown>,
  mockMutate: vi.fn(),
}));

// le même objet sert à tous les tests : chacun règle REACT_APP_PARAPHEUR avant le rendu
vi.mock("@/env", async () => {
  const { makeTestEnv } = await import("@/test/env");
  Object.assign(mockEnv, makeTestEnv({ REACT_APP_PARAPHEUR: "factice" }));
  return { env: mockEnv, validateEnv: () => undefined };
});

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetItem: () => ({ data: { valeursCourantes: [{ valeur: "1 hour" }] } }),
    useGetFullCollection: () => ({
      data: {
        items: [
          {
            "@id": "/composantes/1",
            libelle: "UFR de sciences",
            circuitSignature: "circuit-sciences",
          },
          { "@id": "/composantes/2", libelle: "UFR de droit", circuitSignature: null },
        ],
      },
      isFetching: false,
    }),
    // la requête aboutit : la fenêtre se ferme sur un message de confirmation
    usePatch: (options: { onSuccess: () => void }) => ({
      mutate: (...args: unknown[]) => {
        mockMutate(...args);
        options.onSuccess();
      },
      isPending: false,
    }),
  }),
}));

describe("SignatureElectronique", () => {
  beforeEach(() => {
    mockEnv.REACT_APP_PARAPHEUR = "factice";
    mockMutate.mockReset();
  });

  it("rappelle le parapheur et la fréquence du suivi", () => {
    renderWithProviders(
      <App>
        <SignatureElectronique />
      </App>,
    );

    expect(screen.getByText("factice")).toBeInTheDocument();
    expect(screen.getByText("1 hour")).toBeInTheDocument();
  });

  it("liste les composantes avec leur circuit, ou l'envoi par e-mail", () => {
    renderWithProviders(
      <App>
        <SignatureElectronique />
      </App>,
    );

    const sciences = screen.getByText("UFR de sciences").closest("tr") as HTMLElement;
    expect(within(sciences).getByText("circuit-sciences")).toBeInTheDocument();
    const droit = screen.getByText("UFR de droit").closest("tr") as HTMLElement;
    expect(within(droit).getByText("Aucun : envoi par e-mail")).toBeInTheDocument();
  });

  it("ne modifie que le circuit de la composante, sans toucher à ses référents", async () => {
    renderWithProviders(
      <App>
        <SignatureElectronique />
      </App>,
    );

    const droit = screen.getByText("UFR de droit").closest("tr") as HTMLElement;
    await userEvent.click(within(droit).getByRole("button", { name: /Éditer/ }));
    await userEvent.type(await screen.findByLabelText("Circuit de signature"), "circuit-droit");
    await userEvent.click(screen.getByRole("button", { name: "Enregistrer" }));

    await vi.waitFor(() =>
      expect(mockMutate).toHaveBeenCalledWith({
        "@id": "/composantes/2",
        data: { circuitSignature: "circuit-droit" },
      }),
    );
    expect(
      await screen.findByText("Circuit de signature de la composante modifié"),
    ).toBeInTheDocument();
  });

  it("annonce la signature désactivée sans parapheur", () => {
    mockEnv.REACT_APP_PARAPHEUR = "";

    renderWithProviders(
      <App>
        <SignatureElectronique />
      </App>,
    );

    expect(
      screen.getByText(
        "Aucun parapheur n'est configuré : la signature électronique est désactivée.",
      ),
    ).toBeInTheDocument();
    expect(screen.queryByText("UFR de sciences")).not.toBeInTheDocument();
  });
});
