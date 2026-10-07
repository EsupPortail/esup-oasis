/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { act } from "@testing-library/react";

/** Marge couvrant le `setTimeout(…, 10)` d'antd, avec un peu de jeu. */
const DELAI_PAR_DEFAUT_MS = 20;

/**
 * Laisse s'écouler les timers qu'Ant Design arme au montage d'un `Form.Item`.
 *
 * `Form.Item` affiche ses erreurs en différé via `useDebounce` →
 * `useDelayState` (`@rc-component/util`), qui programme un `setTimeout(…, 10)`.
 * Ce timer n'est **jamais annulé au démontage** : `useDelayState` n'expose aucun
 * cleanup. Un test entièrement synchrone se termine donc avant son échéance, et
 * le `setState` tombe une fois l'environnement de test détruit — d'où l'erreur
 * « This error was caught after test environment was torn down », intermittente
 * puisqu'elle dépend de la charge machine et de l'ordre des workers.
 *
 * À appeler en `afterEach` dans les tests synchrones qui montent un `Form` antd.
 * Les tests qui enchaînent déjà des `await` (`findBy*`, `waitFor`) laissent le
 * temps s'écouler d'eux-mêmes et n'en ont pas besoin.
 *
 * @example
 * afterEach(async () => {
 *   await drainAntdTimers();
 * });
 */
export async function drainAntdTimers(ms: number = DELAI_PAR_DEFAUT_MS): Promise<void> {
  await act(async () => {
    await new Promise((resolve) => {
      setTimeout(resolve, ms);
    });
  });
}
