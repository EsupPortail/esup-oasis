/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

import "@testing-library/jest-dom";
import {toHaveNoViolations} from "vitest-axe/dist/matchers";
import nodeCrypto from "crypto";
import {transferableAbortController} from "node:util";
import {server} from "@/mocks/server";

expect.extend({toHaveNoViolations});

// window.env est injecté au runtime via public/env.js — on le mock pour les tests
(window as unknown as Record<string, unknown>).env = {};

// Mock global de la config runtime : évite les `REACT_APP_*` undefined et la
// répétition d'un `vi.mock("@/env", …)` dans chaque test (cf. src/test/env.ts).
// Un test qui a besoin de valeurs spécifiques surcharge localement via son
// propre `vi.mock("@/env", () => ({ env: makeTestEnv({ … }) }))`.
vi.mock("@/env", async () => {
    const {TEST_ENV} = await import("@/test/env");
    return {env: TEST_ENV, validateEnv: () => undefined};
});

beforeAll(() => server.listen({onUnhandledRequest: "error"}));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());

global.matchMedia =
    global.matchMedia ||
    function () {
        return {
            addListener: vi.fn(),
            removeListener: vi.fn(),
        };
    };

Object.defineProperty(globalThis, "crypto", {
    value: {
        getRandomValues: (arr: Uint8Array) => nodeCrypto.randomBytes(arr.length),
        subtle: nodeCrypto.webcrypto?.subtle,
    },
});

// jsdom remplace AbortController/AbortSignal par une implémentation incompatible
// avec le fetch Node (undici) utilisé par MSW. On rétablit les implémentations Node.
const nodeAbortController = transferableAbortController();
globalThis.AbortController = Object.getPrototypeOf(nodeAbortController).constructor;
globalThis.AbortSignal = Object.getPrototypeOf(nodeAbortController.signal).constructor;

// Ant Design Table (et @rc-component/resize-observer) utilisent ResizeObserver,
// absent de JSDOM — polyfill minimal pour les tests.
if (typeof globalThis.ResizeObserver === "undefined") {
    globalThis.ResizeObserver = class ResizeObserver {
        observe() {
        }

        unobserve() {
        }

        disconnect() {
        }
    };
}

// Node.js 22+ définit localStorage comme undefined sans --localstorage-file.
// Ce polyfill rétablit l'implémentation jsdom sur globalThis pour tous les tests.
if (typeof globalThis.localStorage === "undefined") {
    const store: Record<string, string> = {};
    Object.defineProperty(globalThis, "localStorage", {
        value: {
            getItem: (key: string) => store[key] ?? null,
            setItem: (key: string, value: string) => {
                store[key] = String(value);
            },
            removeItem: (key: string) => {
                delete store[key];
            },
            clear: () => {
                Object.keys(store).forEach((k) => delete store[k]);
            },
            get length() {
                return Object.keys(store).length;
            },
            key: (index: number) => Object.keys(store)[index] ?? null,
        },
        writable: true,
        configurable: true,
    });
}
