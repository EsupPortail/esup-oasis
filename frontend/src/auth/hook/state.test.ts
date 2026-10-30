/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

import {OAUTH_CODE_VERIFIER_KEY, OAUTH_NONCE_KEY, OAUTH_STATE_KEY} from "./constants";
import {
    base64UrlEncode,
    deriveCodeChallenge,
    generateCodeVerifier,
    generateNonce,
    generateState,
    getCodeVerifier,
    getNonce,
    removeCodeVerifier,
    removeNonce,
    removeState,
    saveCodeVerifier,
    saveNonce,
    saveState,
} from "./state";

// ---------------------------------------------------------------------------
// generateState
// ---------------------------------------------------------------------------

describe("generateState", () => {
    it("retourne une chaîne de 40 caractères", () => {
        expect(generateState()).toHaveLength(40);
    });

    it("ne contient que des caractères alphanumériques", () => {
        expect(generateState()).toMatch(/^[A-Za-z0-9]{40}$/);
    });
});

// ---------------------------------------------------------------------------
// saveState / removeState
// ---------------------------------------------------------------------------

describe("saveState", () => {
    beforeEach(() => sessionStorage.clear());

    it("stocke la valeur dans sessionStorage sous la bonne clé", () => {
        saveState("csrf-token");
        expect(sessionStorage.getItem(OAUTH_STATE_KEY)).toBe("csrf-token");
    });
});

// ---------------------------------------------------------------------------
// removeState
// ---------------------------------------------------------------------------

describe("removeState", () => {
    beforeEach(() => sessionStorage.clear());

    it("supprime la valeur de sessionStorage", () => {
        saveState("csrf-token");
        removeState();
        expect(sessionStorage.getItem(OAUTH_STATE_KEY)).toBeNull();
    });
});

// ---------------------------------------------------------------------------
// generateNonce
// ---------------------------------------------------------------------------

describe("generateNonce", () => {
    it("retourne une chaîne de 40 caractères", () => {
        expect(generateNonce()).toHaveLength(40);
    });

    it("ne contient que des caractères alphanumériques", () => {
        expect(generateNonce()).toMatch(/^[A-Za-z0-9]{40}$/);
    });
});

// ---------------------------------------------------------------------------
// saveNonce
// ---------------------------------------------------------------------------

describe("saveNonce", () => {
    beforeEach(() => sessionStorage.clear());

    it("stocke la valeur dans sessionStorage sous la bonne clé", () => {
        saveNonce("replay-nonce");
        expect(sessionStorage.getItem(OAUTH_NONCE_KEY)).toBe("replay-nonce");
    });
});

// ---------------------------------------------------------------------------
// getNonce
// ---------------------------------------------------------------------------

describe("getNonce", () => {
    beforeEach(() => sessionStorage.clear());

    it("retourne la valeur précédemment sauvegardée", () => {
        saveNonce("replay-nonce");
        expect(getNonce()).toBe("replay-nonce");
    });

    it("retourne null si aucun nonce n'est stocké", () => {
        expect(getNonce()).toBeNull();
    });
});

// ---------------------------------------------------------------------------
// removeNonce
// ---------------------------------------------------------------------------

describe("removeNonce", () => {
    beforeEach(() => sessionStorage.clear());

    it("supprime le nonce de sessionStorage", () => {
        saveNonce("replay-nonce");
        removeNonce();
        expect(getNonce()).toBeNull();
    });
});

// ---------------------------------------------------------------------------
// PKCE (RFC 7636)
// ---------------------------------------------------------------------------

describe("base64UrlEncode", () => {
    it("encode correctement en Base64URL sans padding '=' et avec '-' et '_'", () => {
        // 0xfb, 0xff, 0xfe -> base64 est "+//+" -> base64url est "-__-"
        const bytes = new Uint8Array([0xfb, 0xff, 0xfe]);
        const encoded = base64UrlEncode(bytes);
        expect(encoded).toBe("-__-");
        expect(encoded).not.toContain("+");
        expect(encoded).not.toContain("/");
        expect(encoded).not.toContain("=");
    });
});

describe("generateCodeVerifier", () => {
    it("génère un verifier de 86 caractères sans caractères interdits", () => {
        const verifier = generateCodeVerifier();
        // 64 octets en base64url = ceil(64 * 4 / 3) = 86 chars
        expect(verifier).toHaveLength(86);
        expect(verifier).toMatch(/^[A-Za-z0-9_-]{86}$/);
    });
});

describe("deriveCodeChallenge (RFC 7636 Annexe B)", () => {
    it("valide exactement le vecteur de test officiel RFC 7636 Annexe B", async () => {
        const testVerifier = "dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk";
        const expectedChallenge = "E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM";
        const derived = await deriveCodeChallenge(testVerifier);
        expect(derived).toBe(expectedChallenge);
    });
});

describe("saveCodeVerifier / getCodeVerifier / removeCodeVerifier", () => {
    beforeEach(() => sessionStorage.clear());

    it("gère correctement le cycle de vie du code_verifier en sessionStorage", () => {
        expect(getCodeVerifier()).toBeNull();

        saveCodeVerifier("my-verifier-123");
        expect(sessionStorage.getItem(OAUTH_CODE_VERIFIER_KEY)).toBe("my-verifier-123");
        expect(getCodeVerifier()).toBe("my-verifier-123");

        removeCodeVerifier();
        expect(getCodeVerifier()).toBeNull();
    });
});
