/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

// --- GET COLLECTION ---
import { useQuery, UseQueryResult } from "@tanstack/react-query";
import { PaginateResult, RequestMethod } from "@context/api/ApiProvider";
import { useNavigate } from "react-router";
import { ApiPathMethodParameters, ApiPathMethodQuery, ApiPathMethodResponse, Path } from "@api";
import { buildUrl } from "@context/api/ApiContextFn/UrlBuilder";
import {
  handleApiResponse,
  IErreurNotification,
} from "@context/api/ApiContextFn/HandleApiResponse";
import { useAuth } from "@/auth/AuthProvider";

/**
 * Hook for fetching a collection of data from an API using GET method.
 *
 * @template Path - The API path for the collection of data.
 * @template Options - The options for the API request query.
 *
 * @param {Object} options - The options object for the hook.
 * @param {Path} options.path - The API path for the collection of data.
 * @param {Options} [options.options] - The options for the API request query.
 * @param {boolean} [options.enabled] - Flag to enable/disable the hook (default: true).
 * @param {Object} [options.parameters] - Additional parameters for the API request.
 *
 * @return {UseQueryResult} - The result of the useQuery hook.
 *  cf. https://react-query.tanstack.com/reference/useQuery
 *
 * @example
 * // Define the API path and options
 * const path = "/users";
 * const options = { page: 1, pageSize: 10 };
 *
 * // Enable the hook and provide additional parameters
 * const hookOptions = {
 *   path,
 *   options,
 *   enabled: true,
 *   parameters: { sortBy: "name", filter: "active" }
 * };
 *
 * // Use the hook
 * const { data, isLoading, error, refetch } = useApi().useGetCollectionHook(hookOptions);
 */
export type UseGetCollectionHook = <P extends Path>(options: {
  path: P;
  query?: ApiPathMethodQuery<P, "get">;
  enabled?: boolean;
  parameters?: ApiPathMethodParameters<P, "get">;
  onError?: (error: IErreurNotification) => void;
}) => UseQueryResult<PaginateResult<ApiPathMethodResponse<P, "get">>>;

/**
 * A hook for making paginated GET requests to a collection API endpoint.
 *
 * @template Path - The API path for the collection endpoint.
 * @template Options - The additional query options for the GET request.
 *
 * @param {Object} options - The options for the hook.
 * @param {Path} options.path - The API path for the collection endpoint.
 * @param {number} options.page - The page number for the paginated results.
 * @param {number} options.itemsPerPage - The number of items per page.
 * @param {Options} [options.options] - The additional query options for the GET request.
 * @param {boolean} [options.enabled] - Determines if the hook should be enabled or not.
 * @param {Object} [options.parameters] - Additional parameters for the GET request.
 * @param {Function} [options.onSuccess] - A callback function to handle the successful response.
 * @param {ApiPathMethodResponse<Path, "get">} [data] - The response data from the API endpoint.
 *
 * @returns {UseQueryResult<PaginateResult<ApiPathMethodResponse<Path, "get">["hydra:member"]]>}
 *   - The result of the paginated GET request including loading and error state.
 *   cf. https://react-query.tanstack.com/reference/useQuery
 *
 *   @example
 *   const { data, isLoading, error } = useApi().useGetCollectionPaginated({
 *   path: "/evenements",
 *   page: 1,
 *   itemsPerPage: 1000,
 *   options: {
 *            "debut[after]": date.startOf("month").toISOString(),
 *            "fin[before]": date.endOf("month").toISOString(),
 *            },
 *   });
 */
export type UseGetCollectionPaginatedHook = <P extends Path>(options: {
  path: P;
  page?: number;
  itemsPerPage?: number;
  query?: ApiPathMethodQuery<P, "get">;
  enabled?: boolean;
  parameters?: ApiPathMethodParameters<P, "get">;
  onError?: (error: IErreurNotification) => void;
}) => UseQueryResult<PaginateResult<ApiPathMethodResponse<P, "get">>>;

export function useGetCollection<P extends Path>(
  baseUrl: string,
  fetchOptions: RequestInit,
  options: {
    path: P;
    query?: ApiPathMethodQuery<P, "get">;
    enabled?: boolean;
    parameters?: ApiPathMethodParameters<P, "get">;
    onError?: (error: IErreurNotification) => void;
  },
): UseQueryResult<PaginateResult<ApiPathMethodResponse<P, "get">>> {
  const url = buildUrl<P, "get">(
    baseUrl,
    options.path,
    undefined,
    options.parameters,
    options.query,
  );
  const navigate = useNavigate();
  const auth = useAuth();

  //React query fn
  const queryFn = async () => {
    const data = await handleApiResponse(
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

    return {
      items: data["hydra:member"],
      totalItems: data["hydra:totalItems"],
      currentPage:
        options && typeof options === "object" && "page" in options ? options.page : undefined,
      itemsPerPage:
        options && typeof options === "object" && "itemsPerPage" in options
          ? options.itemsPerPage
          : undefined,
    } as PaginateResult<ApiPathMethodResponse<P, "get">>;
  };

  return useQuery({
    queryKey: [options.path, url, auth.user?.uid],
    queryFn,
    enabled: Boolean(url.toString()) && (options.enabled !== undefined ? options.enabled : true),
    // onSuccess: options.onSuccess,
  });
}
