/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

/**
 * Boîte aux lettres en mémoire pour le payload du callback OAuth.
 *
 * Le payload (qui contient l'access_token) ne doit pas transiter par sessionStorage,
 * où il serait lisible par tout script de l'origine (XSS). Comme `/callback` est une
 * route SPA et que le retour vers la racine se fait via react-router (sans rechargement
 * de page), le message peut rester dans la mémoire du module : il ne survit à aucun
 * rechargement et n'est lisible qu'au travers de ces deux fonctions.
 */
export type OAuthCallbackMessage = {
  error?: string;
  payload?: Record<string, string>;
};

let pendingMessage: OAuthCallbackMessage | null = null;
let listener: ((message: OAuthCallbackMessage) => void) | null = null;

/**
 * Dépose le message du callback OAuth.
 * Consommé immédiatement si un abonné est présent, sinon mis en attente
 * jusqu'au prochain abonnement (l'ordre de montage des composants n'est pas garanti).
 */
export function deliverCallbackMessage(message: OAuthCallbackMessage): void {
  if (listener) {
    listener(message);
  } else {
    pendingMessage = message;
  }
}

/**
 * S'abonne au message du callback OAuth. Un éventuel message en attente est
 * consommé immédiatement. Retourne la fonction de désabonnement.
 */
export function subscribeCallbackMessage(
  fn: (message: OAuthCallbackMessage) => void,
): VoidFunction {
  listener = fn;
  if (pendingMessage) {
    const message = pendingMessage;
    pendingMessage = null;
    fn(message);
  }
  return () => {
    if (listener === fn) listener = null;
  };
}
