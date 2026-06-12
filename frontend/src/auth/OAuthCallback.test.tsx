/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { render, screen } from "@testing-library/react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import React from "react";
import OAuthCallback from "./OAuthCallback";
import { OAUTH_STATE_KEY } from "@/auth/hook/constants";
import { OAuthCallbackMessage, subscribeCallbackMessage } from "@/auth/hook/callbackPayload";

vi.mock("@controls/Spinner/Spinner", () => ({
  default: () => <div data-testid="spinner" role="status" aria-label="Chargement en cours" />,
}));

const { navigateMock } = vi.hoisted(() => ({ navigateMock: vi.fn() }));
vi.mock("react-router-dom", async (importOriginal) => ({
  ...(await importOriginal<typeof import("react-router-dom")>()),
  useNavigate: () => navigateMock,
}));

// --- Helpers ---

function buildHash(params: Record<string, string>): string {
  return `#${new URLSearchParams(params).toString()}`;
}

/** Consomme le message en attente dans la boîte aux lettres mémoire (null si aucun). */
function consumeMessage(): OAuthCallbackMessage | null {
  let message: OAuthCallbackMessage | null = null;
  const unsubscribe = subscribeCallbackMessage((m) => {
    message = m;
  });
  unsubscribe();
  return message;
}

// --- Tests ---

describe("OAuthCallback", () => {
  beforeEach(() => {
    sessionStorage.clear();
    consumeMessage(); // purge un éventuel message laissé par un test précédent

    vi.stubGlobal("location", {
      hash: "",
      origin: "http://localhost",
    });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    navigateMock.mockClear();
  });

  describe("rendu", () => {
    it("rend le spinner par défaut", () => {
      render(<OAuthCallback />);
      expect(screen.getByTestId("spinner")).toBeInTheDocument();
    });

    it("rend le composant custom fourni via la prop Component", () => {
      render(<OAuthCallback Component={<div data-testid="custom-loader">Chargement…</div>} />);
      expect(screen.getByTestId("custom-loader")).toBeInTheDocument();
      expect(screen.queryByTestId("spinner")).not.toBeInTheDocument();
    });
  });

  describe("state valide", () => {
    it("délivre le payload en mémoire (jamais en sessionStorage)", () => {
      sessionStorage.setItem(OAUTH_STATE_KEY, "state-valide");
      window.location.hash = buildHash({
        access_token: "token-abc",
        state: "state-valide",
        token_type: "bearer",
      });

      render(<OAuthCallback />);

      const message = consumeMessage();
      expect(message?.payload).toMatchObject({
        access_token: "token-abc",
        state: "state-valide",
        token_type: "bearer",
      });
      expect(message?.error).toBeUndefined();

      // Le token ne doit apparaître nulle part dans le sessionStorage
      const sessionDump = Object.keys(sessionStorage)
        .map((k) => sessionStorage.getItem(k))
        .join("|");
      expect(sessionDump).not.toContain("token-abc");
    });

    it("revient à la racine via une navigation SPA en remplaçant l'entrée d'historique", () => {
      sessionStorage.setItem(OAUTH_STATE_KEY, "state-valide");
      window.location.hash = buildHash({ access_token: "token-abc", state: "state-valide" });

      render(<OAuthCallback />);

      expect(navigateMock).toHaveBeenCalledWith("/", { replace: true });
    });
  });

  describe("state invalide (mismatch CSRF)", () => {
    it("délivre une erreur state mismatch si le state reçu ne correspond pas", () => {
      sessionStorage.setItem(OAUTH_STATE_KEY, "state-attendu");
      window.location.hash = buildHash({
        access_token: "token-abc",
        state: "state-pirate",
      });

      render(<OAuthCallback />);

      const message = consumeMessage();
      expect(message?.error).toBe("OAuth error: State mismatch.");
      expect(message?.payload).toBeUndefined();
    });

    it("navigue quand même en cas de mismatch", () => {
      sessionStorage.setItem(OAUTH_STATE_KEY, "state-attendu");
      window.location.hash = buildHash({ access_token: "token-abc", state: "state-pirate" });

      render(<OAuthCallback />);

      expect(navigateMock).toHaveBeenCalledWith("/", { replace: true });
    });

    it("délivre une erreur state mismatch si sessionStorage ne contient aucun state", () => {
      window.location.hash = buildHash({ access_token: "token-abc", state: "state-quelconque" });

      render(<OAuthCallback />);

      expect(consumeMessage()?.error).toBe("OAuth error: State mismatch.");
    });
  });

  describe("erreur OAuth dans le hash", () => {
    it("délivre l'erreur décodée si error est présent dans le hash", () => {
      window.location.hash = buildHash({ error: "access_denied" });

      render(<OAuthCallback />);

      const message = consumeMessage();
      expect(message?.error).toBe("access_denied");
      expect(message?.payload).toBeUndefined();
    });

    it("décode les caractères URI dans le message d'erreur", () => {
      window.location.hash = buildHash({ error: "access%20denied" });

      render(<OAuthCallback />);

      expect(consumeMessage()?.error).toBe("access denied");
    });

    it("navigue quand même en cas d'erreur OAuth", () => {
      window.location.hash = buildHash({ error: "access_denied" });

      render(<OAuthCallback />);

      expect(navigateMock).toHaveBeenCalledWith("/", { replace: true });
    });
  });

  describe("livraison unique (garde StrictMode)", () => {
    it("ne délivre le message qu'une seule fois même si l'effet est rejoué", () => {
      sessionStorage.setItem(OAUTH_STATE_KEY, "state-valide");
      window.location.hash = buildHash({ access_token: "tok", state: "state-valide" });

      const messages: OAuthCallbackMessage[] = [];
      const unsubscribe = subscribeCallbackMessage((m) => messages.push(m));

      const { rerender } = render(<OAuthCallback />);
      rerender(<OAuthCallback />);

      unsubscribe();
      expect(messages).toHaveLength(1);
    });
  });

  describe("accessibilité (axe-core)", () => {
    it("n'a pas de violations sur le rendu par défaut", async () => {
      const { container } = render(<OAuthCallback />);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });
  });
});
