/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { beforeEach, describe, expect, it, vi } from "vitest";
import { act, render, renderHook, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { PREF_KEY_EXPORT_INSCRIPTIONS, useExportInscriptions } from "./useExportInscriptions";

const mockUser = { isReferentComposante: false, isPlanificateur: true };
const mockSetPreference = vi.fn();
let mockPreference: string | undefined;

vi.mock("@/auth/AuthProvider", () => ({ useAuth: () => ({ user: mockUser }) }));
vi.mock("@context/utilisateurPreferences/UtilisateurPreferencesProvider", () => ({
  usePreferences: () => ({
    getPreference: () => mockPreference,
    setPreference: mockSetPreference,
  }),
}));

describe("useExportInscriptions", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockPreference = undefined;
    mockUser.isReferentComposante = false;
    mockUser.isPlanificateur = true;
  });

  it("ouvre la modale avec le dernier choix mémorisé, puis démarre l'export et mémorise le choix", async () => {
    mockPreference = "toutes";
    const demarrer = vi.fn();
    const { result, rerender } = renderHook(() => useExportInscriptions());

    act(() => result.current.demanderPortee(demarrer));
    rerender();
    render(<>{result.current.modalPortee}</>);

    expect(screen.getByRole("radio", { name: /^Toutes les inscriptions/ })).toBeChecked();
    expect(demarrer).not.toHaveBeenCalled();

    await userEvent.click(
      screen.getByRole("radio", { name: /^Inscription principale uniquement/ }),
    );
    await userEvent.click(screen.getByRole("button", { name: "Exporter" }));

    expect(demarrer).toHaveBeenCalledOnce();
    expect(mockSetPreference).toHaveBeenCalledWith(PREF_KEY_EXPORT_INSCRIPTIONS, "principale");
    rerender();
    expect(result.current.portee).toBe("principale");
  });

  it("présélectionne « en cours » sans préférence valide", () => {
    mockPreference = "n'importe quoi";
    const { result } = renderHook(() => useExportInscriptions());

    act(() => result.current.demanderPortee(vi.fn()));
    render(<>{result.current.modalPortee}</>);

    expect(screen.getByRole("radio", { name: /^Inscriptions en cours/ })).toBeChecked();
  });

  it("annulation de la modale : l'export ne démarre pas et rien n'est mémorisé", async () => {
    const demarrer = vi.fn();
    const { result } = renderHook(() => useExportInscriptions());

    act(() => result.current.demanderPortee(demarrer));
    const { rerender } = render(<>{result.current.modalPortee}</>);
    await userEvent.click(screen.getByRole("button", { name: "Annuler" }));
    rerender(<>{result.current.modalPortee}</>);

    expect(demarrer).not.toHaveBeenCalled();
    expect(mockSetPreference).not.toHaveBeenCalled();
    expect(result.current.modalPortee).toBeFalsy();
  });

  it("référent ET planificateur : la modale est affichée", () => {
    mockUser.isReferentComposante = true;
    mockUser.isPlanificateur = true;
    const demarrer = vi.fn();
    const { result } = renderHook(() => useExportInscriptions());

    act(() => result.current.demanderPortee(demarrer));

    expect(demarrer).not.toHaveBeenCalled();
    expect(result.current.modalPortee).toBeTruthy();
  });

  it("référent de composante : démarre directement, sur les inscriptions en cours", () => {
    mockUser.isReferentComposante = true;
    mockUser.isPlanificateur = false;
    mockPreference = "toutes";
    const demarrer = vi.fn();
    const { result } = renderHook(() => useExportInscriptions());

    act(() => result.current.demanderPortee(demarrer));

    expect(demarrer).toHaveBeenCalledOnce();
    expect(result.current.modalPortee).toBeFalsy();
    expect(result.current.portee).toBe("en_cours");
    expect(mockSetPreference).not.toHaveBeenCalled();
  });
});
