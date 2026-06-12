/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

/**
 * Convert object to query string
 * @param object
 */
export const objectToQuery = (object: Record<string, string>) => {
  return new URLSearchParams(object).toString();
};

/**
 * Convert query string to object
 * @param query
 */
export const queryToObject = (query: string) => {
  const parameters = new URLSearchParams(query);
  return Object.fromEntries(parameters.entries());
};

/**
 * Construit une URL `mailto:` en encodant l'adresse, afin qu'une adresse
 * contenant `?`, `&` ou `#` ne puisse pas injecter de paramètres (cc, bcc, body…).
 * @param email adresse du destinataire (donnée potentiellement issue de l'API)
 */
export const mailtoHref = (email: string): string => `mailto:${encodeURIComponent(email)}`;
