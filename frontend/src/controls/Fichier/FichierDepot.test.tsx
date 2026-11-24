/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

import {render, screen, fireEvent, waitFor} from "@testing-library/react";
import {App} from "antd";
import {describe, it, expect, vi, beforeEach, afterEach} from "vitest";
import {drainAntdTimers} from "@/test";
import {FichierDepot} from "./FichierDepot";

const mockValiderFichier = vi.fn();
vi.mock("@utils/fichierValidation", () => ({
    ACCEPT_FICHIERS: ".pdf,.png",
    validerFichier: (file: File) => mockValiderFichier(file),
}));

const mockEnvoyer = vi.fn();
vi.mock("@utils/upload", () => ({
    envoyerFichierFetch: (
        _url: string,
        _auth: unknown,
        file: File,
        onSuccess: (f: unknown) => void,
        onError: (e: Error) => void,
    ) => mockEnvoyer(file, onSuccess, onError),
}));

vi.mock("@/auth/AuthProvider", () => ({useAuth: () => ({user: {uid: "u"}})}));

function renderDepot(onAdded = vi.fn(), onError = vi.fn()) {
    render(
        <App>
            <FichierDepot onAdded={onAdded} onError={onError}/>
        </App>,
    );
    return {onAdded, onError};
}

function selectFile(name = "doc.pdf", type = "application/pdf") {
    const input = document.querySelector('input[type="file"]') as HTMLInputElement;
    const file = new File(["contenu"], name, {type});
    fireEvent.change(input, {target: {files: [file]}});
    return file;
}

describe("FichierDepot", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockValiderFichier.mockReturnValue(undefined);
        mockEnvoyer.mockImplementation((_file, onSuccess) =>
            onSuccess({"@id": "/telechargements/1"}),
        );
    });

    // UploadList/ListItem d'antd arme un setTimeout(…, 300) via useDelayState non
    // annulé au démontage (cf. drainAntdTimers) : on le laisse s'écouler avant la
    // destruction de l'environnement jsdom.
    afterEach(async () => {
        await drainAntdTimers(350);
    });

    it("affiche la zone de dépôt", () => {
        renderDepot();
        expect(screen.getByText(/déposez un fichier/i)).toBeInTheDocument();
    });

    it("fichier valide : envoie le fichier et notifie onAdded avec la ressource créée", async () => {
        const {onAdded} = renderDepot();
        selectFile();

        await waitFor(() => expect(mockEnvoyer).toHaveBeenCalled());
        expect(onAdded).toHaveBeenCalledWith({"@id": "/telechargements/1"});
    });

    it("fichier invalide : n'envoie rien (beforeUpload bloque)", async () => {
        mockValiderFichier.mockReturnValue("Fichier trop volumineux");
        renderDepot();
        selectFile("trop-gros.pdf");

        // Laisse la file d'attente microtâches s'écouler.
        await Promise.resolve();
        expect(mockEnvoyer).not.toHaveBeenCalled();
    });

    it("erreur d'envoi : propage onError", async () => {
        const erreur = new Error("réseau");
        mockEnvoyer.mockImplementation((_file, _onSuccess, onError) => onError(erreur));
        const {onError} = renderDepot();
        selectFile();

        await waitFor(() => expect(onError).toHaveBeenCalledWith(erreur));
    });
});
