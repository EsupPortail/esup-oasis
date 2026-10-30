/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

import {describe, it, expect, beforeEach} from "vitest";

// `@/env` est mocké globalement (setupTests) ; ici on importe la VRAIE implémentation
// pour valider `validateEnv` / la lecture de `window.env`.
const REQUIRED = {
    REACT_APP_API: "http://api.test",
    REACT_APP_OAUTH_PROVIDER: "http://oauth.test",
    REACT_APP_OAUTH_CLIENT_ID: "client",
    REACT_APP_FRONTEND: "http://localhost",
};

async function importRealEnv(windowEnv: Record<string, unknown>) {
    vi.resetModules();
    (window as unknown as Record<string, unknown>).env = windowEnv;
    return vi.importActual<typeof import("@/env")>("@/env");
}

describe("env — validateEnv", () => {
    beforeEach(() => {
        (window as unknown as Record<string, unknown>).env = {};
    });

    it("ne lève pas quand toutes les variables requises sont présentes", async () => {
        const {validateEnv} = await importRealEnv(REQUIRED);
        expect(() => validateEnv()).not.toThrow();
    });
});

describe("env — fanions de fonctionnalité (toFeatureEnabled)", () => {
    // window.env est prioritaire : ces surcharges sont déterministes quel que soit import.meta.env.
    it('interprète la chaîne "true" de window.env comme le booléen true', async () => {
        const {env} = await importRealEnv({...REQUIRED, REACT_APP_DARKMODE: "true"});
        expect(env.REACT_APP_DARKMODE).toBe(true);
    });

    it('interprète la chaîne "false" de window.env comme le booléen false', async () => {
        const {env} = await importRealEnv({...REQUIRED, REACT_APP_GERER_DEMANDES: "false"});
        expect(env.REACT_APP_GERER_DEMANDES).toBe(false);
    });

    it("gère REACT_APP_OAUTH_PKCE (par défaut true, surchargeable à false)", async () => {
        const defaultEnv = await importRealEnv(REQUIRED);
        expect(defaultEnv.env.REACT_APP_OAUTH_PKCE).toBe(true);

        const falseEnv = await importRealEnv({...REQUIRED, REACT_APP_OAUTH_PKCE: "false"});
        expect(falseEnv.env.REACT_APP_OAUTH_PKCE).toBe(false);
    });

    it("gère REACT_APP_OAUTH_SCOPE (par défaut 'profile', surchargeable à 'openid profile')", async () => {
        const defaultEnv = await importRealEnv(REQUIRED);
        expect(defaultEnv.env.REACT_APP_OAUTH_SCOPE).toBe("profile");

        const customEnv = await importRealEnv({...REQUIRED, REACT_APP_OAUTH_SCOPE: "openid profile"});
        expect(customEnv.env.REACT_APP_OAUTH_SCOPE).toBe("openid profile");
    });
});
