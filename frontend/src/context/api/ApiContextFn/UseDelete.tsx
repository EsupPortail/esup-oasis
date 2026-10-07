/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

// --- DELETE ITEM ---
import { QueryClient, useMutation, UseMutationResult } from "@tanstack/react-query";
import { handleApiResponse } from "@context/api/ApiContextFn/HandleApiResponse";
import { handleInvalidation } from "@context/api/ApiContextFn/HandleInvalidation";
import { MutationDeleteParams, RequestMethod } from "@context/api/ApiProvider";
import { useNavigate } from "react-router";
import { ApiPathMethodResponse, Path } from "@api";
import { useAuth } from "@/auth/AuthProvider";
import { logger } from "@utils/logger";

/**
 * Mutation DELETE typée sur un endpoint API.
 *
 * @param options.path - Endpoint API. Utilisé uniquement pour l'inférence de types.
 * @param options.invalidationQueryKeys - Clés React Query à invalider après suppression. Utiliser les constantes `QK_*` de `queryKeys.ts`.
 * @param options.onSuccess - Appelé après succès et invalidation du cache.
 *
 * @remarks `mutation.mutate()` attend `{ "@id": string }` — l'`@id` est l'IRI de la ressource (ex. `/evenements/42`).
 * Le serveur retourne 204 No Content ; `onSuccess` reçoit `undefined` comme `data`.
 */
export type UseDeleteHook = <P extends Path>(options: {
  path: P;
  invalidationQueryKeys?: string[];
  onSuccess?: (variables: MutationDeleteParams) => void;
}) => UseMutationResult<ApiPathMethodResponse<P, "delete">, unknown, MutationDeleteParams>;

export function useDelete<P extends Path>(
  baseUrl: string,
  fetchOptions: RequestInit,
  client: QueryClient,
  options: {
    path: P;
    invalidationQueryKeys?: string[];
    onSuccess?: (variables: MutationDeleteParams) => void;
  },
): UseMutationResult<ApiPathMethodResponse<P, "delete">, unknown, MutationDeleteParams> {
  const navigate = useNavigate();
  const auth = useAuth();
  return useMutation({
    mutationFn: async (params: MutationDeleteParams) => {
      const url = new URL(params["@id"], baseUrl);
      return handleApiResponse(
        RequestMethod.DELETE,
        await fetch(url, {
          ...fetchOptions,
          method: "DELETE",
        }),
        navigate,
        auth,
        options,
      );
    },
    onSuccess: (_data, variables) => {
      if (options.invalidationQueryKeys) handleInvalidation(client, options.invalidationQueryKeys);
      if (options.onSuccess) options.onSuccess(variables);
    },
    onError: (error) => {
      logger.error(error);
    },
  });
}
