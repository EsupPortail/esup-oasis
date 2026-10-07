/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

// --- PATCH ITEM ---
import { QueryClient, useMutation, UseMutationResult } from "@tanstack/react-query";
import { handleApiResponse } from "@context/api/ApiContextFn/HandleApiResponse";
import { handleInvalidation } from "@context/api/ApiContextFn/HandleInvalidation";
import { MutationPatchParams, RequestMethod } from "@context/api/ApiProvider";
import { useNavigate } from "react-router";
import { ApiContentTypePatch, ApiPathMethodResponse, Path } from "@api";
import { useAuth } from "@/auth/AuthProvider";
import { logger } from "@utils/logger";

/**
 * Mutation PATCH typée sur un endpoint API.
 *
 * @param options.path - Endpoint API (ex. `"/evenements/{id}"`). Utilisé uniquement pour l'inférence de types.
 * @param options.invalidationQueryKeys - Clés React Query à invalider après succès (matching par `startsWith`). Utiliser les constantes `QK_*` de `queryKeys.ts`.
 * @param options.onSuccess - Appelé après succès et invalidation du cache.
 * @param options.onError - Appelé en cas d'erreur réseau ou HTTP.
 *
 * @remarks `mutation.mutate()` attend `{ "@id": string, data: ... }` — l'`@id` est l'IRI de la ressource (ex. `/evenements/42`), pas l'`id` numérique.
 */
export type UsePatchHook = <P extends Path>(options: {
  path: P;
  invalidationQueryKeys?: string[];
  onSuccess?: (data: ApiPathMethodResponse<P, "patch">, variables: MutationPatchParams<P>) => void;
  onError?: (error: unknown) => void;
}) => UseMutationResult<ApiPathMethodResponse<P, "patch">, unknown, MutationPatchParams<P>>;

export function usePatch<P extends Path>(
  baseUrl: string,
  fetchOptions: RequestInit,
  client: QueryClient,
  options: {
    path: P;
    invalidationQueryKeys?: string[];
    onSuccess?: (
      data: ApiPathMethodResponse<P, "patch">,
      variables: MutationPatchParams<P>,
    ) => void;
    onError?: (error: unknown) => void;
  },
): UseMutationResult<ApiPathMethodResponse<P, "patch">, unknown, MutationPatchParams<P>> {
  const navigate = useNavigate();
  const auth = useAuth();

  return useMutation({
    mutationFn: async (params: MutationPatchParams<P>) => {
      const url = new URL(params["@id"], baseUrl);
      return handleApiResponse(
        RequestMethod.PATCH,
        await fetch(url, {
          ...fetchOptions,
          headers: {
            ...fetchOptions.headers,
            "Content-Type": ApiContentTypePatch,
          },
          method: "PATCH",
          body: JSON.stringify(params.data),
        }),
        navigate,
        auth,
        options,
        JSON.stringify(params.data, null, 2),
      );
    },
    onSuccess: (data, variables) => {
      // Évite la race condition entre fermeture du composant et invalidation async.
      client.setQueriesData({ queryKey: [variables["@id"]] }, data);

      if (options.invalidationQueryKeys) handleInvalidation(client, options.invalidationQueryKeys);
      if (options.onSuccess) options.onSuccess(data, variables);
    },
    onError: (error) => {
      logger.error(error);
      if (options.onError) options.onError(error);
    },
  });
}
