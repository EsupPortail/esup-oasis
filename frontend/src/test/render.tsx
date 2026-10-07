/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, ReactNode } from "react";
import {
  render,
  renderHook,
  RenderHookOptions,
  RenderHookResult,
  RenderOptions,
  RenderResult,
} from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { MemoryRouter, MemoryRouterProps } from "react-router";
import { createTestQueryClient } from "./queryClient";

/**
 * Socle de rendu commun aux tests de composants et de hooks.
 *
 * Fournit, dans le bon ordre, les providers dont dépend la quasi-totalité de
 * l'application :
 * - un `QueryClient` isolé (cf. {@link createTestQueryClient}) ;
 * - un `MemoryRouter` (routage sans navigateur, point d'entrée configurable).
 *
 * Les providers spécifiques (Auth, Api, Theme…) restent volontairement hors du
 * socle : selon le test on les **mocke** (`vi.mock("@context/api/ApiProvider")`)
 * ou on les monte explicitement via l'option `wrapper`/`extraWrappers`. Cela évite
 * de tirer toute la pile (et ses dépendances à `env`) dans des tests unitaires.
 */

export interface ProvidersOptions {
  /** Client à réutiliser. Par défaut une instance fraîche et isolée. */
  queryClient?: QueryClient;
  /** Monter un `MemoryRouter` (défaut : `true`). */
  withRouter?: boolean;
  /** Props transmis au `MemoryRouter` (ex. `initialEntries`). */
  routerProps?: MemoryRouterProps;
  /** Raccourci pour `routerProps.initialEntries = [route]`. */
  route?: string;
  /**
   * Providers additionnels à imbriquer **à l'intérieur** du routeur
   * (ex. AuthProvider/ApiProvider réels pour un test d'intégration).
   * Appliqués dans l'ordre du tableau (le premier est le plus externe).
   */
  extraWrappers?: Array<(children: ReactNode) => ReactElement>;
}

/** Construit l'arbre de providers et renvoie le client utilisé (pour les assertions de cache). */
function buildWrapper(options: ProvidersOptions = {}): {
  Wrapper: React.FC<{ children: ReactNode }>;
  queryClient: QueryClient;
} {
  const queryClient = options.queryClient ?? createTestQueryClient();
  const withRouter = options.withRouter ?? true;
  const routerProps: MemoryRouterProps = {
    ...(options.route ? { initialEntries: [options.route] } : {}),
    ...options.routerProps,
  };

  const Wrapper: React.FC<{ children: ReactNode }> = ({ children }) => {
    let tree: ReactNode = children;

    // Wrappers additionnels : le premier du tableau enveloppe les suivants.
    for (const wrap of [...(options.extraWrappers ?? [])].reverse()) {
      tree = wrap(tree);
    }

    if (withRouter) {
      tree = <MemoryRouter {...routerProps}>{tree}</MemoryRouter>;
    }

    return <QueryClientProvider client={queryClient}>{tree}</QueryClientProvider>;
  };

  return { Wrapper, queryClient };
}

export interface RenderWithProvidersResult extends RenderResult {
  /** Le `QueryClient` monté (pour inspecter/invalider le cache dans le test). */
  queryClient: QueryClient;
}

/**
 * Rend un composant avec le socle de providers.
 *
 * @example
 * const { queryClient } = renderWithProviders(<DemandeTable refs={refs} />);
 *
 * @example route initiale
 * renderWithProviders(<MonProfil />, { route: "/mon-profil" });
 */
export function renderWithProviders(
  ui: ReactElement,
  {
    queryClient,
    withRouter,
    routerProps,
    route,
    extraWrappers,
    ...renderOptions
  }: ProvidersOptions & Omit<RenderOptions, "wrapper"> = {},
): RenderWithProvidersResult {
  const { Wrapper, queryClient: client } = buildWrapper({
    queryClient,
    withRouter,
    routerProps,
    route,
    extraWrappers,
  });

  return {
    queryClient: client,
    ...render(ui, { wrapper: Wrapper, ...renderOptions }),
  };
}

export interface RenderHookWithProvidersResult<Result, Props> extends RenderHookResult<
  Result,
  Props
> {
  queryClient: QueryClient;
}

/**
 * Rend un hook avec le socle de providers — l'équivalent de {@link renderWithProviders}
 * pour `renderHook`. Idéal pour les hooks de la couche API.
 *
 * @example
 * const { result, queryClient } = renderHookWithProviders(() =>
 *   useGetFullCollection(BASE_URL, OPTS, { path: "/utilisateurs" }),
 * );
 */
export function renderHookWithProviders<Result, Props>(
  callback: (props: Props) => Result,
  {
    queryClient,
    withRouter,
    routerProps,
    route,
    extraWrappers,
    ...hookOptions
  }: ProvidersOptions & Omit<RenderHookOptions<Props>, "wrapper"> = {},
): RenderHookWithProvidersResult<Result, Props> {
  const { Wrapper, queryClient: client } = buildWrapper({
    queryClient,
    withRouter: withRouter ?? false, // par défaut un hook n'a pas besoin de routeur
    routerProps,
    route,
    extraWrappers,
  });

  return {
    queryClient: client,
    ...renderHook(callback, { wrapper: Wrapper, ...hookOptions }),
  };
}
