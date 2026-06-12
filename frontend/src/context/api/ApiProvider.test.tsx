/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { render, renderHook } from "@testing-library/react";
import { QueryClient } from "@tanstack/react-query";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { ApiProvider, useApi } from "./ApiProvider";

vi.mock("@context/api/ApiContextFn/UseGetItem", () => ({
  useGetItem: vi.fn(() => ({ data: undefined, isLoading: false })),
}));
vi.mock("@context/api/ApiContextFn/UseGetCollection", () => ({
  useGetCollection: vi.fn(() => ({ data: undefined, isLoading: false })),
}));
vi.mock("@context/api/ApiContextFn/UseGetFullCollection", () => ({
  useGetFullCollection: vi.fn(() => ({ data: undefined, isLoading: false })),
}));
vi.mock("@context/api/ApiContextFn/UsePatch", () => ({
  usePatch: vi.fn(() => ({ mutate: vi.fn(), status: "idle" })),
}));
vi.mock("@context/api/ApiContextFn/UsePut", () => ({
  usePut: vi.fn(() => ({ mutate: vi.fn(), status: "idle" })),
}));
vi.mock("@context/api/ApiContextFn/UsePost", () => ({
  usePost: vi.fn(() => ({ mutate: vi.fn(), status: "idle" })),
}));
vi.mock("@context/api/ApiContextFn/UseDelete", () => ({
  useDelete: vi.fn(() => ({ mutate: vi.fn(), status: "idle" })),
}));
vi.mock("@context/api/ApiContextFn/HandleInvalidation", () => ({
  handleInvalidation: vi.fn(),
}));

import { useGetItem as innerUseGetItem } from "@context/api/ApiContextFn/UseGetItem";

function makeClient() {
  return new QueryClient({ defaultOptions: { queries: { retry: false } } });
}

describe("ApiProvider", () => {
  let client: QueryClient;

  beforeEach(() => {
    client = makeClient();
    vi.clearAllMocks();
  });

  it("useApi() hors contexte lève une erreur explicite", () => {
    const spy = vi.spyOn(console, "error").mockImplementation(() => {});
    expect(() => renderHook(() => useApi())).toThrow(
      "useApi doit être utilisé dans un <ApiProvider>",
    );
    spy.mockRestore();
  });

  describe("impersonate", () => {
    function Consumer() {
      const api = useApi();
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      api.useGetItem({ path: "/test" as any });
      return null;
    }

    it("ajoute X-Switch-User quand auth.impersonate est défini", () => {
      render(
        <ApiProvider
          baseUrl="https://api.test"
          auth={{ impersonate: "impersonate@test.fr" } as never}
          client={client}
        >
          <Consumer />
        </ApiProvider>,
      );
      expect(vi.mocked(innerUseGetItem)).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          headers: expect.objectContaining({ "X-Switch-User": "impersonate@test.fr" }),
        }),
        expect.anything(),
      );
    });

    it("n'ajoute pas X-Switch-User sans impersonate", () => {
      render(
        <ApiProvider baseUrl="https://api.test" client={client}>
          <Consumer />
        </ApiProvider>,
      );
      const fetchOptions = vi.mocked(innerUseGetItem).mock.calls[0][1] as RequestInit & {
        headers: Record<string, string>;
      };
      expect(fetchOptions.headers).not.toHaveProperty("X-Switch-User");
    });
  });
});
