/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { ApiPathMethodParameters, ApiPathMethodQuery, Method, Path } from "@api";
import { env } from "@/env";

/**
 * Construit l'URL complète d'un appel API.
 *
 * **Contrat IRI pour `parameters`** : chaque valeur doit être une IRI API Platform complète
 * (ex. `"/types_evenements/1"`), pas un id scalaire. L'algorithme supprime le segment de
 * path qui précède le placeholder (`{key}`) et le remplace par la valeur — ce qui ne
 * fonctionne correctement que si la valeur ré-apporte ce segment (comportement d'une IRI).
 * Passer un scalaire (`"1"`) produit une URL silencieusement fausse.
 *
 * Si `url` est fourni (IRI concrète), il est utilisé tel quel à la place de `path` et
 * `parameters` est ignoré (l'IRI est déjà résolu).
 */
export function buildUrl<P extends Path, M extends Method>(
  baseUrl: string,
  path: P,
  url?: string,
  parameters?: ApiPathMethodParameters<P, M>,
  query?: ApiPathMethodQuery<P, M>,
): URL {
  let resPath: string = url || path;

  // paramètres de l'URL
  if (parameters) {
    Object.entries(parameters).forEach(([key, value]) => {
      const paramIndex = resPath.split("/").indexOf(`{${key}}`);
      // on remplace (paramIndex-1) + paramIndex par la valeur
      // ex: /types_evenements/{typeId}/taux/{id} => /types_evenements/1/taux/{id}

      resPath = resPath
        .split("/")
        .map((p, index) => {
          if (index === paramIndex - 1) {
            return null;
          }
          if (index === paramIndex) {
            return value;
          } else {
            return p;
          }
        })
        .filter((x) => x !== null && x !== undefined && x !== "")
        .join("/");
    });
  }

  const apiPrefix = env.REACT_APP_API_PREFIX.replace(/\/$/, "");
  const normalizedResPath = resPath.startsWith("/") ? resPath : `/${resPath}`;
  const finalPath =
    normalizedResPath.startsWith(`${apiPrefix}/`) || normalizedResPath === apiPrefix
      ? normalizedResPath
      : `${apiPrefix}${normalizedResPath}`;

  const resUrl = new URL(finalPath, baseUrl);

  // query string
  if (query) {
    Object.entries(query)
      // sort entries by key
      .sort(([key1], [key2]) => key1.localeCompare(key2))
      .filter(([, value]) => value !== undefined && value !== null)
      .forEach(([key, value]) => {
        if (Array.isArray(value)) {
          value.forEach((v) => resUrl.searchParams.append(`${key}`, v.toString()));
        } else {
          resUrl.searchParams.set(key, (value as string | Date | boolean | number).toString());
        }
      });
  }

  return resUrl;
}
