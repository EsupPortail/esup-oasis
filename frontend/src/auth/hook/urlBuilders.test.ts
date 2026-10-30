/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

import {enhanceAuthorizeUrl, formatExchangeCodeForTokenServerURL} from "./urlBuilders";

// ---------------------------------------------------------------------------
// enhanceAuthorizeUrl
// ---------------------------------------------------------------------------

describe("enhanceAuthorizeUrl", () => {
    it("inclut les paramètres OAuth requis dans la query string", () => {
        const url = new URL(
            enhanceAuthorizeUrl(
                "https://auth.example.com/authorize",
                "client123",
                "https://app.example.com/callback",
                "openid",
                "state-xyz",
                "nonce-abc",
                "code",
                undefined,
            ),
        );
        expect(url.searchParams.get("client_id")).toBe("client123");
        expect(url.searchParams.get("response_type")).toBe("code");
        expect(url.searchParams.get("redirect_uri")).toBe("https://app.example.com/callback");
        expect(url.searchParams.get("scope")).toBe("openid");
        expect(url.searchParams.get("state")).toBe("state-xyz");
        expect(url.searchParams.get("nonce")).toBe("nonce-abc");
    });

    it("inclut les extraQueryParameters quand fournis", () => {
        const url = new URL(
            enhanceAuthorizeUrl(
                "https://auth.example.com/authorize",
                "client123",
                "https://app.example.com/callback",
                "",
                "s",
                "n",
                "token",
                {acr_values: "urn:esup:loa1", max_age: 3600},
            ),
        );
        expect(url.searchParams.get("acr_values")).toBe("urn:esup:loa1");
        expect(url.searchParams.get("max_age")).toBe("3600");
    });

    it("inclut scope même s'il est vide", () => {
        const url = new URL(
            enhanceAuthorizeUrl(
                "https://auth.example.com/authorize",
                "c",
                "https://app.example.com/callback",
                "",
                "s",
                "n",
                "token",
                undefined,
            ),
        );
        expect(url.searchParams.has("scope")).toBe(true);
        expect(url.searchParams.get("scope")).toBe("");
    });

    it("inclut code_challenge et code_challenge_method si fournis", () => {
        const url = new URL(
            enhanceAuthorizeUrl(
                "https://auth.example.com/authorize",
                "client123",
                "https://app.example.com/callback",
                "openid profile",
                "state-xyz",
                "nonce-abc",
                "code",
                undefined,
                "challenge-s256",
                "S256",
            ),
        );
        expect(url.searchParams.get("code_challenge")).toBe("challenge-s256");
        expect(url.searchParams.get("code_challenge_method")).toBe("S256");
        expect(url.searchParams.get("scope")).toBe("openid profile");
    });
});

// ---------------------------------------------------------------------------
// formatExchangeCodeForTokenServerURL
// ---------------------------------------------------------------------------

describe("formatExchangeCodeForTokenServerURL", () => {
    it("ajoute les paramètres OAuth à une URL sans query string existante", () => {
        const url = new URL(
            formatExchangeCodeForTokenServerURL(
                "https://api.example.com/token",
                "client123",
                "auth-code-xyz",
                "https://app.example.com/callback",
                "state-abc",
            ),
        );
        expect(url.searchParams.get("client_id")).toBe("client123");
        expect(url.searchParams.get("grant_type")).toBe("authorization_code");
        expect(url.searchParams.get("code")).toBe("auth-code-xyz");
        expect(url.searchParams.get("redirect_uri")).toBe("https://app.example.com/callback");
        expect(url.searchParams.get("state")).toBe("state-abc");
    });

    it("préserve les paramètres existants de l'URL du serveur", () => {
        const url = new URL(
            formatExchangeCodeForTokenServerURL(
                "https://api.example.com/token?custom=preserved",
                "client123",
                "code",
                "https://app.example.com/callback",
                "state",
            ),
        );
        expect(url.searchParams.get("custom")).toBe("preserved");
        expect(url.searchParams.get("grant_type")).toBe("authorization_code");
    });

    it("n'ajoute pas de paramètre parasite quand l'URL n'a pas de query string", () => {
        const url = new URL(
            formatExchangeCodeForTokenServerURL(
                "https://api.example.com/token",
                "client123",
                "code",
                "https://app.example.com/callback",
                "state",
            ),
        );
        const paramKeys = Array.from(url.searchParams.keys());
        expect(paramKeys).toEqual(
            expect.arrayContaining(["client_id", "grant_type", "code", "redirect_uri", "state"]),
        );
        expect(paramKeys).toHaveLength(5);
    });
});
