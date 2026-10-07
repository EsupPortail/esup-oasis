/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { screen } from "@testing-library/react";
import { useLocation } from "react-router";
import { describe, it, expect } from "vitest";
import { renderWithProviders, renderHookWithProviders } from "./render";
import { createTestQueryClient } from "./queryClient";
import { hydraCollection, hydraPage } from "./hydra";

describe("socle de test — renderWithProviders", () => {
  it("monte le composant avec un QueryClient et un Router par défaut", () => {
    const { queryClient } = renderWithProviders(<div>contenu</div>);
    expect(screen.getByText("contenu")).toBeInTheDocument();
    expect(queryClient).toBeDefined();
  });

  it("route : injecte l'entrée initiale dans le MemoryRouter", () => {
    function ShowPath() {
      return <span>{useLocation().pathname}</span>;
    }
    renderWithProviders(<ShowPath />, { route: "/mon-profil" });
    expect(screen.getByText("/mon-profil")).toBeInTheDocument();
  });

  it("réutilise le queryClient fourni", () => {
    const client = createTestQueryClient();
    const { queryClient } = renderWithProviders(<div>x</div>, { queryClient: client });
    expect(queryClient).toBe(client);
  });

  it("extraWrappers : imbrique un provider additionnel", () => {
    const Ctx = React.createContext("default");
    const { container } = renderWithProviders(
      <Ctx.Consumer>{(v) => <span>{v}</span>}</Ctx.Consumer>,
      { extraWrappers: [(children) => <Ctx.Provider value="fourni">{children}</Ctx.Provider>] },
    );
    expect(container).toHaveTextContent("fourni");
  });
});

describe("socle de test — renderHookWithProviders", () => {
  it("rend un hook avec accès au QueryClient", () => {
    const { result, queryClient } = renderHookWithProviders(() => 42);
    expect(result.current).toBe(42);
    expect(queryClient).toBeDefined();
  });
});

describe("socle de test — helpers Hydra", () => {
  it("hydraCollection : enrobe les membres et déduit le total", () => {
    expect(hydraCollection([1, 2, 3])).toEqual({
      "hydra:member": [1, 2, 3],
      "hydra:totalItems": 3,
    });
  });

  it("hydraPage : découpe selon page/itemsPerPage et conserve le total global", () => {
    const page2 = hydraPage([1, 2, 3, 4, 5], 2, 2);
    expect(page2["hydra:member"]).toEqual([3, 4]);
    expect(page2["hydra:totalItems"]).toBe(5);
  });
});
