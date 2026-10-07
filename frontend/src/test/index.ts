/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

/**
 * Socle de test partagé.
 *
 * Point d'entrée unique : `import { renderWithProviders, makeGestionnaire, hydraCollection } from "@/test"`.
 */

export { createTestQueryClient } from "./queryClient";
export { hydraCollection, hydraPage, type HydraCollection } from "./hydra";
export {
  renderWithProviders,
  renderHookWithProviders,
  type ProvidersOptions,
  type RenderWithProvidersResult,
  type RenderHookWithProvidersResult,
} from "./render";
export { drainAntdTimers } from "./timers";
export * from "./fixtures";
