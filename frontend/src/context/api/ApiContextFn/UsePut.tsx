/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

// --- PUT ITEM ---
import { QueryClient, useMutation, UseMutationResult } from "@tanstack/react-query";
import { handleApiResponse } from "@context/api/ApiContextFn/HandleApiResponse";
import { handleInvalidation } from "@context/api/ApiContextFn/HandleInvalidation";
import { MutationPutParams, RequestMethod } from "@context/api/ApiProvider";
import { useNavigate } from "react-router";
import { ApiPathMethodResponse, Path } from "@api";
import { useAuth } from "@/auth/AuthProvider";
import { logger } from "@utils/logger";

/**
 * Mutation PUT typée sur un endpoint API (remplacement complet de la ressource).
 *
 * @param options.path - Endpoint API. Utilisé uniquement pour l'inférence de types.
 * @param options.invalidationQueryKeys - Clés React Query à invalider après succès. Utiliser les constantes `QK_*` de `queryKeys.ts`.
 * @param options.onSuccess - Appelé après succès et invalidation du cache, reçoit la ressource mise à jour.
 * @param options.onError - Appelé en cas d'erreur réseau ou HTTP.
 *
 * @remarks Préférer `usePatch` pour les mises à jour partielles. `usePut` envoie le corps complet sans `Content-Type: merge-patch+json`.
 */
export type UsePutHook = <P extends Path>(options: {
  path: P;
  invalidationQueryKeys?: string[];
  onSuccess?: (data: ApiPathMethodResponse<P, "put">, variables: MutationPutParams<P>) => void;
  onError?: (error: unknown) => void;
}) => UseMutationResult<ApiPathMethodResponse<P, "put">, unknown, MutationPutParams<P>>;

export function usePut<P extends Path>(
  baseUrl: string,
  fetchOptions: RequestInit,
  client: QueryClient,
  options: {
    path: P;
    invalidationQueryKeys?: string[];
    onSuccess?: (data: ApiPathMethodResponse<P, "put">, variables: MutationPutParams<P>) => void;
    onError?: (error: unknown) => void;
  },
): UseMutationResult<ApiPathMethodResponse<P, "put">, unknown, MutationPutParams<P>> {
  const navigate = useNavigate();
  const auth = useAuth();

  return useMutation({
    mutationFn: async (params: MutationPutParams<P>) => {
      const url = new URL(params["@id"], baseUrl);
      return handleApiResponse(
        RequestMethod.PUT,
        await fetch(url, {
          ...fetchOptions,
          headers: {
            ...fetchOptions.headers,
          },
          method: "PUT",
          body: JSON.stringify(params.data),
        }),
        navigate,
        auth,
        options,
        JSON.stringify(params.data, null, 2),
        options.onError,
      );
    },
    onSuccess: (data, variables) => {
      if (options.invalidationQueryKeys) handleInvalidation(client, options.invalidationQueryKeys);
      if (options.onSuccess) options.onSuccess(data, variables);
    },
    onError: (error) => {
      logger.error(error);
      if (options.onError) options.onError(error);
    },
  });
}
