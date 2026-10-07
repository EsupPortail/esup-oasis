import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { envoyerFichierFetch } from "./upload";
import type { AuthContextType } from "@/auth/AuthProvider";

vi.mock("@utils/logger", () => ({
  logger: { log: vi.fn(), info: vi.fn(), warn: vi.fn(), error: vi.fn(), debug: vi.fn() },
}));

vi.mock("@/env", async () => {
  const { makeTestEnv } = await import("@/test/env");
  return { env: makeTestEnv({ REACT_APP_API_PREFIX: "/api" }) };
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const API_URL = "http://api.test";
const NO_IMPERSONATE = { impersonate: undefined, token: "tok" } as unknown as AuthContextType;
const WITH_IMPERSONATE = {
  impersonate: "agent@test.fr",
  token: "tok",
} as unknown as AuthContextType;

type FakeXhr = {
  open: ReturnType<typeof vi.fn>;
  send: ReturnType<typeof vi.fn>;
  setRequestHeader: ReturnType<typeof vi.fn>;
  withCredentials: boolean;
  status: number;
  responseText: string;
  upload: { onprogress: ((e: ProgressEvent) => void) | null };
  onload: (() => void) | null;
  onerror: (() => void) | null;
};

let fakeXhr: FakeXhr;

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("envoyerFichierFetch", () => {
  beforeEach(() => {
    fakeXhr = {
      open: vi.fn(),
      send: vi.fn(),
      setRequestHeader: vi.fn(),
      withCredentials: false,
      status: 200,
      responseText: "{}",
      upload: { onprogress: null },
      onload: null,
      onerror: null,
    };
    vi.stubGlobal(
      "XMLHttpRequest",
      vi.fn(function () {
        return fakeXhr;
      }),
    );
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  it("upload réussi (status 200) → onSuccess appelé avec le JSON parsé", async () => {
    fakeXhr.responseText = JSON.stringify({ id: "/telechargements/1" });
    const onSuccess = vi.fn();

    const promise = envoyerFichierFetch(API_URL, NO_IMPERSONATE, new Blob(), onSuccess);
    fakeXhr.onload?.();

    await promise;
    expect(onSuccess).toHaveBeenCalledWith({ id: "/telechargements/1" });
  });

  it("upload réussi (status 201) → onSuccess appelé", async () => {
    fakeXhr.status = 201;
    fakeXhr.responseText = JSON.stringify({ id: "/telechargements/2" });
    const onSuccess = vi.fn();

    const promise = envoyerFichierFetch(API_URL, NO_IMPERSONATE, new Blob(), onSuccess);
    fakeXhr.onload?.();

    await promise;
    expect(onSuccess).toHaveBeenCalledWith({ id: "/telechargements/2" });
  });

  it.each([[400], [500]])(
    "erreur serveur (status %d) → onError appelé, promise résolue sans throw",
    async (status) => {
      fakeXhr.status = status;
      const onError = vi.fn();

      const promise = envoyerFichierFetch(API_URL, NO_IMPERSONATE, new Blob(), vi.fn(), onError);
      fakeXhr.onload?.();

      await expect(promise).resolves.toBeUndefined();
      expect(onError).toHaveBeenCalledWith(expect.any(Error));
    },
  );

  it("erreur réseau (onerror) → onError appelé, promise résolue", async () => {
    const onError = vi.fn();

    const promise = envoyerFichierFetch(API_URL, NO_IMPERSONATE, new Blob(), vi.fn(), onError);
    fakeXhr.onerror?.();

    await expect(promise).resolves.toBeUndefined();
    expect(onError).toHaveBeenCalledWith(expect.any(Error));
  });

  it("JSON mal formé dans la réponse → onError appelé, pas de throw", async () => {
    fakeXhr.responseText = "pas du json {{{";
    const onError = vi.fn();

    const promise = envoyerFichierFetch(API_URL, NO_IMPERSONATE, new Blob(), vi.fn(), onError);
    fakeXhr.onload?.();

    await expect(promise).resolves.toBeUndefined();
    expect(onError).toHaveBeenCalledWith(expect.any(Error));
  });

  it("onProgress appelé avec le bon pourcentage", async () => {
    const onProgress = vi.fn();

    const promise = envoyerFichierFetch(
      API_URL,
      NO_IMPERSONATE,
      new Blob(),
      vi.fn(),
      undefined,
      onProgress,
    );

    fakeXhr.upload.onprogress?.({
      lengthComputable: true,
      loaded: 50,
      total: 100,
    } as ProgressEvent);

    fakeXhr.responseText = "{}";
    fakeXhr.onload?.();

    await promise;
    expect(onProgress).toHaveBeenCalledWith(50);
  });

  it("impersonate défini → header X-Switch-User positionné", async () => {
    const promise = envoyerFichierFetch(API_URL, WITH_IMPERSONATE, new Blob(), vi.fn());
    fakeXhr.onload?.();
    await promise;

    expect(fakeXhr.setRequestHeader).toHaveBeenCalledWith("X-Switch-User", "agent@test.fr");
  });

  it("impersonate absent → header X-Switch-User absent", async () => {
    const promise = envoyerFichierFetch(API_URL, NO_IMPERSONATE, new Blob(), vi.fn());
    fakeXhr.onload?.();
    await promise;

    expect(fakeXhr.setRequestHeader).not.toHaveBeenCalledWith("X-Switch-User", expect.any(String));
  });

  it("URL construite : apiUrl + REACT_APP_API_PREFIX + /telechargements", async () => {
    const promise = envoyerFichierFetch(API_URL, NO_IMPERSONATE, new Blob(), vi.fn());
    fakeXhr.onload?.();
    await promise;

    expect(fakeXhr.open).toHaveBeenCalledWith("POST", `${API_URL}/api/telechargements`);
  });

  // --- Progression ----------------------------------------------------------

  it("onProgress non appelé si lengthComputable est false", async () => {
    const onProgress = vi.fn();

    const promise = envoyerFichierFetch(
      API_URL,
      NO_IMPERSONATE,
      new Blob(),
      vi.fn(),
      undefined,
      onProgress,
    );

    fakeXhr.upload.onprogress?.({
      lengthComputable: false,
      loaded: 50,
      total: 100,
    } as ProgressEvent);

    fakeXhr.responseText = "{}";
    fakeXhr.onload?.();
    await promise;

    expect(onProgress).not.toHaveBeenCalled();
  });

  it("onProgress arrondit le pourcentage (Math.round)", async () => {
    const onProgress = vi.fn();

    const promise = envoyerFichierFetch(
      API_URL,
      NO_IMPERSONATE,
      new Blob(),
      vi.fn(),
      undefined,
      onProgress,
    );

    // 1/3 ≈ 33.33 → Math.round → 33
    fakeXhr.upload.onprogress?.({ lengthComputable: true, loaded: 1, total: 3 } as ProgressEvent);
    // 2/3 ≈ 66.67 → Math.round → 67
    fakeXhr.upload.onprogress?.({ lengthComputable: true, loaded: 2, total: 3 } as ProgressEvent);

    fakeXhr.responseText = "{}";
    fakeXhr.onload?.();
    await promise;

    expect(onProgress).toHaveBeenNthCalledWith(1, 33);
    expect(onProgress).toHaveBeenNthCalledWith(2, 67);
  });

  it("onProgress absent → aucune erreur lors de l'événement de progression", async () => {
    const promise = envoyerFichierFetch(API_URL, NO_IMPERSONATE, new Blob(), vi.fn());

    // Pas de onProgress fourni : le callback optionnel doit être ignoré silencieusement.
    expect(() => {
      fakeXhr.upload.onprogress?.({
        lengthComputable: true,
        loaded: 50,
        total: 100,
      } as ProgressEvent);
    }).not.toThrow();

    fakeXhr.responseText = "{}";
    fakeXhr.onload?.();
    await promise;
  });
});
