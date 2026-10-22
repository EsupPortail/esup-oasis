/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

import {OAUTH_CODE_VERIFIER_KEY, OAUTH_NONCE_KEY, OAUTH_STATE_KEY} from "@/auth/hook/constants";

const getCrypto = (): Crypto => {
    if (typeof window !== "undefined" && window.crypto) {
        return window.crypto;
    }
    if (typeof globalThis !== "undefined" && globalThis.crypto) {
        return globalThis.crypto;
    }
    throw new Error("Crypto is not supported in this environment");
};

const getSubtleCrypto = (): SubtleCrypto => {
    if (typeof window !== "undefined" && window.crypto?.subtle) {
        return window.crypto.subtle;
    }
    if (typeof globalThis !== "undefined" && globalThis.crypto?.subtle) {
        return globalThis.crypto.subtle;
    }
    throw new Error("SubtleCrypto is not supported in this environment");
};

const TOKEN_BYTES = 20; // 40 caractères hexadécimaux (hex : distribution uniforme)

const generateToken = (): string => {
    const randomValues = new Uint8Array(TOKEN_BYTES);
    getCrypto().getRandomValues(randomValues);
    return Array.from(randomValues, (byte) => byte.toString(16).padStart(2, "0")).join("");
};

// --- State (protection CSRF)
export const generateState = (): string => generateToken();
export const saveState = (state: string): void => sessionStorage.setItem(OAUTH_STATE_KEY, state);
export const removeState = (): void => sessionStorage.removeItem(OAUTH_STATE_KEY);

// --- Nonce (protection replay — vérifié par le serveur si OIDC, envoyé systématiquement)
export const generateNonce = (): string => generateToken();
export const saveNonce = (nonce: string): void => sessionStorage.setItem(OAUTH_NONCE_KEY, nonce);
export const getNonce = (): string | null => sessionStorage.getItem(OAUTH_NONCE_KEY);
export const removeNonce = (): void => sessionStorage.removeItem(OAUTH_NONCE_KEY);

// --- PKCE (RFC 7636)
export const base64UrlEncode = (bytes: Uint8Array): string => {
    let binary = "";
    for (let i = 0; i < bytes.byteLength; i += 1) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
};

export const generateCodeVerifier = (): string => {
    const array = new Uint8Array(64);
    getCrypto().getRandomValues(array);
    return base64UrlEncode(array);
};

export const deriveCodeChallenge = async (verifier: string): Promise<string> => {
    const data = new TextEncoder().encode(verifier);
    const subtle = getSubtleCrypto();
    const digest = await subtle.digest("SHA-256", data);
    return base64UrlEncode(new Uint8Array(digest));
};

export const saveCodeVerifier = (codeVerifier: string): void =>
    sessionStorage.setItem(OAUTH_CODE_VERIFIER_KEY, codeVerifier);
export const getCodeVerifier = (): string | null => sessionStorage.getItem(OAUTH_CODE_VERIFIER_KEY);
export const removeCodeVerifier = (): void => sessionStorage.removeItem(OAUTH_CODE_VERIFIER_KEY);
