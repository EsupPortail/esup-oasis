/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

vi.mock("antd", () => ({
  notification: { error: vi.fn() },
  Button: () => null,
  Space: () => null,
}));
vi.mock("@ant-design/icons", () => ({
  CopyOutlined: () => null,
  LoginOutlined: () => null,
}));
vi.mock("@/queryClient", () => ({
  queryClient: { clear: vi.fn() },
}));
vi.mock("@utils/logger", () => ({
  logger: { error: vi.fn() },
}));
vi.mock("@context/api/ApiProvider", () => ({
  RequestMethod: { GET: "GET", POST: "POST", DELETE: "DELETE", PATCH: "PATCH", PUT: "PUT" },
}));

import { notification } from "antd";
import { queryClient } from "@/queryClient";
import { handleApiResponse } from "./HandleApiResponse";
import type { RequestMethod } from "@context/api/ApiProvider";
import type { AuthContextType } from "@/auth/AuthProvider";

const GET = "GET" as unknown as RequestMethod;
const DELETE = "DELETE" as unknown as RequestMethod;

const makeResponse = (status: number, body = {}, statusText = "") =>
  ({
    status,
    statusText,
    url: "https://api.example.com/test",
    json: vi.fn().mockResolvedValue(body),
  }) as unknown as Response;

const makeAuth = (): AuthContextType => ({ signOut: vi.fn() }) as unknown as AuthContextType;
const navigate = vi.fn();

// ---------------------------------------------------------------------------
// handleApiResponse — réponses réussies
// ---------------------------------------------------------------------------

describe("handleApiResponse — réponses réussies", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });
  afterEach(() => vi.useRealTimers());

  it("retourne le JSON parsé pour une réponse 200", async () => {
    const body = { id: 1, name: "test" };
    const result = await handleApiResponse(GET, makeResponse(200, body), navigate, makeAuth());
    expect(result).toEqual(body);
  });

  it("retourne undefined pour une réponse 204 (No Content)", async () => {
    const result = await handleApiResponse(DELETE, makeResponse(204), navigate, makeAuth());
    expect(result).toBeUndefined();
  });
});

// ---------------------------------------------------------------------------
// handleApiResponse — erreurs HTTP
// ---------------------------------------------------------------------------

describe("handleApiResponse — erreurs HTTP", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });
  afterEach(() => vi.useRealTimers());

  it("lève une Error contenant le statut HTTP pour une réponse 4xx", async () => {
    await expect(
      handleApiResponse(GET, makeResponse(400, {}, "Bad Request"), navigate, makeAuth()),
    ).rejects.toThrow("HTTP error: 400 Bad Request");
  });

  it("appelle notification.error en l'absence de callback onError", async () => {
    await expect(
      handleApiResponse(GET, makeResponse(403, {}, "Forbidden-A"), navigate, makeAuth()),
    ).rejects.toThrow();
    expect(notification.error).toHaveBeenCalledOnce();
  });
});

// ---------------------------------------------------------------------------
// handleApiResponse — callback onError
// ---------------------------------------------------------------------------

describe("handleApiResponse — callback onError", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });
  afterEach(() => vi.useRealTimers());

  it("appelle onError au lieu de notification.error si fourni", async () => {
    const onError = vi.fn();
    await expect(
      handleApiResponse(
        GET,
        makeResponse(403, {}, "Forbidden-B"),
        navigate,
        makeAuth(),
        undefined,
        undefined,
        onError,
      ),
    ).rejects.toThrow();
    expect(onError).toHaveBeenCalledOnce();
    expect(notification.error).not.toHaveBeenCalled();
  });

  it("passe hydra:description comme statusText de la notification", async () => {
    const onError = vi.fn();
    await expect(
      handleApiResponse(
        GET,
        makeResponse(422, { "hydra:description": "Champ invalide" }, "Unprocessable-A"),
        navigate,
        makeAuth(),
        undefined,
        undefined,
        onError,
      ),
    ).rejects.toThrow();
    expect(onError.mock.calls[0][0].statusText).toBe("Champ invalide");
  });

  it("passe data.detail comme statusText si hydra:description est absent", async () => {
    const onError = vi.fn();
    await expect(
      handleApiResponse(
        GET,
        makeResponse(422, { detail: "Contrainte violée" }, "Unprocessable-B"),
        navigate,
        makeAuth(),
        undefined,
        undefined,
        onError,
      ),
    ).rejects.toThrow();
    expect(onError.mock.calls[0][0].statusText).toBe("Contrainte violée");
  });
});

// ---------------------------------------------------------------------------
// handleApiResponse — erreur 401
// ---------------------------------------------------------------------------

describe("handleApiResponse — erreur 401", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });
  afterEach(() => vi.useRealTimers());

  // La garde anti-rafale est un état de module : on rejoue le callback de signOut
  // après chaque test pour la réinitialiser (comme le ferait le vrai signOut).
  function resetSignOutGuard(auth: AuthContextType) {
    const callback = vi.mocked(auth.signOut).mock.calls[0]?.[0];
    callback?.();
  }

  it("vide le cache React Query et déconnecte immédiatement l'utilisateur", async () => {
    const auth = makeAuth();
    await expect(
      handleApiResponse(GET, makeResponse(401, {}, "Unauthorized"), navigate, auth),
    ).rejects.toThrow();
    expect(queryClient.clear).toHaveBeenCalledOnce();
    expect(auth.signOut).toHaveBeenCalledOnce();
    resetSignOutGuard(auth);
  });

  it("rafale de 401 → une seule déconnexion", async () => {
    const auth = makeAuth();
    await expect(
      handleApiResponse(GET, makeResponse(401, {}, "Unauthorized-A"), navigate, auth),
    ).rejects.toThrow();
    await expect(
      handleApiResponse(GET, makeResponse(401, {}, "Unauthorized-B"), navigate, auth),
    ).rejects.toThrow();
    expect(queryClient.clear).toHaveBeenCalledOnce();
    expect(auth.signOut).toHaveBeenCalledOnce();
    resetSignOutGuard(auth);
  });
});

// ---------------------------------------------------------------------------
// handleApiResponse — déduplication
// ---------------------------------------------------------------------------

describe("handleApiResponse — déduplication", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });
  afterEach(() => vi.useRealTimers());

  it("n'affiche pas deux fois la même erreur dans la fenêtre de 1,5 s", async () => {
    const response = () => makeResponse(500, {}, "DupError-1");
    await expect(handleApiResponse(GET, response(), navigate, makeAuth())).rejects.toThrow();
    await expect(handleApiResponse(GET, response(), navigate, makeAuth())).rejects.toThrow();
    expect(notification.error).toHaveBeenCalledOnce();
  });

  it("affiche à nouveau la même erreur après expiration de 1,5 s", async () => {
    const response = () => makeResponse(500, {}, "DupError-2");
    await expect(handleApiResponse(GET, response(), navigate, makeAuth())).rejects.toThrow();
    vi.advanceTimersByTime(1500);
    await expect(handleApiResponse(GET, response(), navigate, makeAuth())).rejects.toThrow();
    expect(notification.error).toHaveBeenCalledTimes(2);
  });
});
