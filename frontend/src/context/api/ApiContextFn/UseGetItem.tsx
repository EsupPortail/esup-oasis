/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

// --- GET ITEM ---
import { useQuery, UseQueryResult } from "@tanstack/react-query";
import {
  handleApiResponse,
  IErreurNotification,
} from "@context/api/ApiContextFn/HandleApiResponse";
import { useNavigate } from "react-router";
import { ApiPathMethodParameters, ApiPathMethodQuery, ApiPathMethodResponse, Path } from "@api";
import { buildUrl } from "@context/api/ApiContextFn/UrlBuilder";
import { RequestMethod } from "@context/api/ApiProvider";
import { useAuth } from "@/auth/AuthProvider";

/**
 * Hook for fetching a single item data from an API using GET method.
 *
 * @template P - The path type that defines the API endpoint path.
 * @param {Object} options - An object containing the hook options.
 * @param {P} options.path - The path of the API endpoint.
 * @param {string} [options.url] - The custom URL to be used for the API request.
 * @param {boolean} [options.enabled] - Determines if the hook is enabled or not.
 * @param {ApiPathMethodParameters<P, "get">} [options.parameters] - The parameters to be included in the API request.
 * @param {ApiPathMethodQuery<P, "get">} [options.query] - The query parameters to be included in the API request.
 * @returns {UseQueryResult<ApiPathMethodResponse<P, "get">>} - The result of the useQuery hook.
 */
export type UseGetItemHook = <P extends Path>(options: {
  path: P;
  url?: string;
  enabled?: boolean;
  parameters?: ApiPathMethodParameters<P, "get">;
  query?: ApiPathMethodQuery<P, "get">;
  onError?: (error: IErreurNotification) => void;
}) => UseQueryResult<ApiPathMethodResponse<P, "get">>;

export function useGetItem<P extends Path>(
  baseUrl: string,
  fetchOptions: RequestInit,
  options: {
    path: P;
    url?: string;
    enabled?: boolean;
    parameters?: ApiPathMethodParameters<P, "get">;
    query?: ApiPathMethodQuery<P, "get">;
    onError?: (error: IErreurNotification) => void;
  },
): UseQueryResult<ApiPathMethodResponse<P, "get">> {
  // URL : useQuery.enabled prévient les URL non valides
  const url = buildUrl<P, "get">(
    baseUrl,
    options.path,
    options.url,
    options.parameters,
    options.query,
  );
  const navigate = useNavigate();
  const auth = useAuth();

  const queryFn = async () => {
    return handleApiResponse(
      RequestMethod.GET,
      await fetch(url, {
        method: "GET",
        ...fetchOptions,
      }),
      navigate,
      auth,
      options,
      undefined,
      options.onError,
    );
  };

  return useQuery<ApiPathMethodResponse<P, "get">>({
    queryKey: [options.url, url, auth.user?.uid, options.path],
    queryFn,
    enabled: Boolean(url) && (options.enabled !== undefined ? options.enabled : true),
  });
}
