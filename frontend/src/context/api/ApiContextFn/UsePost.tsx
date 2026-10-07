/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

// --- POST ITEM ---
import { QueryClient, useMutation, UseMutationResult } from "@tanstack/react-query";
import { handleApiResponse } from "@context/api/ApiContextFn/HandleApiResponse";
import { handleInvalidation } from "@context/api/ApiContextFn/HandleInvalidation";
import { MutationPostParams, RequestMethod } from "@context/api/ApiProvider";
import { useNavigate } from "react-router";
import { ApiPathMethodParameters, ApiPathMethodResponse, Path } from "@api";
import { buildUrl } from "@context/api/ApiContextFn/UrlBuilder";
import { useAuth } from "@/auth/AuthProvider";
import { logger } from "@utils/logger";

/**
 * Mutation POST typée sur un endpoint API.
 *
 * @param options.path - Endpoint API (ex. `"/evenements"`). Utilisé pour l'inférence de types et la construction de l'URL.
 * @param options.url - URL complète en remplacement de `path` (cas des sous-ressources dynamiques).
 * @param options.invalidationQueryKeys - Clés React Query à invalider après succès (matching par `startsWith`). Utiliser les constantes `QK_*` de `queryKeys.ts`.
 * @param options.onSuccess - Appelé après succès et invalidation du cache, reçoit la ressource créée.
 * @param options.parameters - Paramètres de chemin si `path` contient des segments dynamiques.
 */
export type UsePostHook = <P extends Path>(options: {
  path: P;
  url?: string;
  invalidationQueryKeys?: string[];
  onSuccess?: (data: ApiPathMethodResponse<P, "post">, variables: MutationPostParams<P>) => void;
  onError?: () => void;
  parameters?: ApiPathMethodParameters<P, "post">;
}) => UseMutationResult<ApiPathMethodResponse<P, "post">, unknown, MutationPostParams<P>>;

export function usePost<P extends Path>(
  baseUrl: string,
  fetchOptions: RequestInit,
  client: QueryClient,
  options: {
    path: P;
    url?: string;
    invalidationQueryKeys?: string[];
    onSuccess?: (data: ApiPathMethodResponse<P, "post">, variables: MutationPostParams<P>) => void;
    onError?: () => void;
    parameters?: ApiPathMethodParameters<P, "post">;
  },
): UseMutationResult<ApiPathMethodResponse<P, "post">, unknown, MutationPostParams<P>> {
  const navigate = useNavigate();
  const auth = useAuth();

  return useMutation({
    mutationFn: async (params: MutationPostParams<P>) => {
      const url = buildUrl<P, "post">(baseUrl, options.path, options.url, options.parameters);
      return handleApiResponse(
        RequestMethod.POST,
        await fetch(url, {
          ...fetchOptions,
          method: "POST",
          body: JSON.stringify(params.data),
        }),
        navigate,
        auth,
        options,
        JSON.stringify(params.data, null, 2),
      );
    },
    onSuccess: (data, variables) => {
      if (data && typeof data === "object" && "@id" in data && data["@id"]) {
        client.setQueryData([data["@id"]], data);
      }
      if (options.invalidationQueryKeys) handleInvalidation(client, options.invalidationQueryKeys);
      if (options.onSuccess) options.onSuccess(data, variables);
    },
    onError: (error) => {
      logger.error(error);
      if (options.onError) options.onError();
    },
  });
}
