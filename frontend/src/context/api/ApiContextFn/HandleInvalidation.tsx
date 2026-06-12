/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

// --- INVALIDATION ---
import { QueryClient } from "@tanstack/react-query";
import { env } from "@/env";

/** Invalide manuellement des entrées du cache React Query (usage hors mutation). Pour les mutations, préférer `invalidationQueryKeys` dans les options du hook. */
export type UseInvalidationHook = (queryKeys: string[], onSuccess?: VoidFunction) => void;

/**
 * Invalide les entrées du cache React Query dont **un** des éléments de chemin commence par l'une des
 * `queryKeys` fournies.
 *
 * @remarks Le matching teste chaque élément de la `queryKey` qui est une chaîne commençant par `/`
 * (segments de chemin et IRIs), via `String.startsWith`. Le garde `typeof === "string"` ignore l'objet
 * `URL` des collections, l'`uid` et tout autre discriminant. Tester tous les éléments couvre à la fois
 * l'IRI concrète d'un item (`"/utilisateurs/123/avis_ese/45"`) et le **template** présent en fin de clé
 * (`"/utilisateurs/{uid}/avis_ese/{id}"`) : une constante `QK_*` à placeholders matche donc ses items,
 * et un item chargé sans IRI (dont `queryKey[0]` est `undefined`) reste invalidable.
 * Une clé `"/evenements"` invalide donc `/evenements`, `/evenements/42`, `/evenements?page=1`, etc.
 * Utiliser les constantes `QK_*` de `queryKeys.ts` pour éviter les chaînes littérales.
 */
export function handleInvalidation(
  client: QueryClient,
  queryKeys: string[],
  onSuccess?: VoidFunction,
) {
  const prefix = env.REACT_APP_API_PREFIX ?? "";
  client
    .invalidateQueries({
      predicate: (query) =>
        Array.isArray(query.queryKey) &&
        query.queryKey.some((el) => {
          if (typeof el !== "string" || !el.startsWith("/")) return false;
          const normalized = prefix && el.startsWith(prefix) ? el.slice(prefix.length) : el;
          return queryKeys.some((qk) => normalized.startsWith(qk));
        }),
    })
    .then(() => onSuccess && onSuccess());
}
