/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import type { TestingLibraryMatchers } from "@testing-library/jest-dom/matchers";

// Les types livrés par @testing-library/jest-dom passaient jusqu'ici par l'augmentation
// du namespace global `jest`, dont `Assertion` héritait en vitest <= 4. Vitest 5 ne
// dérive plus de `jest.Matchers` et expose `Matchers<R, T>` comme point d'extension
// officiel : on y branche nous-mêmes les matchers jest-dom.
declare module "vitest" {
  // eslint-disable-next-line @typescript-eslint/no-empty-object-type
  interface Matchers<
    R extends void | Promise<void> = void | Promise<void>,
  > extends TestingLibraryMatchers<unknown, R> {}
}
