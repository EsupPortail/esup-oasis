/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useMemo } from "react";
import { QueryClient, UseMutationResult, UseQueryResult } from "@tanstack/react-query";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { handleInvalidation, UseInvalidationHook } from "./ApiContextFn/HandleInvalidation";
import { useGetItem, UseGetItemHook } from "./ApiContextFn/UseGetItem";
import {
   useGetCollection,
   UseGetCollectionHook,
   UseGetCollectionPaginatedHook,
} from "./ApiContextFn/UseGetCollection";
import { usePatch, UsePatchHook } from "./ApiContextFn/UsePatch";
import { usePost, UsePostHook } from "./ApiContextFn/UsePost";
import { useDelete, UseDeleteHook } from "./ApiContextFn/UseDelete";
import { usePrefetch, UsePrefetchHook } from "./ApiContextFn/UsePrefetch";
import { AuthContextType } from "../../auth/AuthProvider";
import {
   ApiContentType,
   ApiPathMethodParameters,
   ApiPathMethodQuery,
   ApiPathMethodRequestBody,
   ApiPathMethodResponse,
   ContentTypePatch,
   Path,
} from "../../api/SchemaHelpers";
import { usePut, UsePutHook } from "./ApiContextFn/UsePut";
import { IErreurNotification } from "./ApiContextFn/HandleApiResponse";

export enum RequestMethod {
   GET = "GET",
   POST = "POST",
   DELETE = "DELETE",
   PATCH = "PATCH",
   PUT = "PUT",
}

export interface PaginateResult<T> {
   items: "hydra:member" extends keyof T ? T["hydra:member"] : T;
   totalItems: number;
   currentPage: number;
   itemsPerPage: number;
}

export interface MutationPatchParams<P extends Path> {
   data: ApiPathMethodRequestBody<P, "patch", ContentTypePatch>;
   "@id": string;
}

export interface MutationPutParams<P extends Path> {
   data: ApiPathMethodRequestBody<P, "put">;
   "@id": string;
}

export interface MutationPostParams<P extends Path> {
   data: ApiPathMethodRequestBody<P, "post">;
}

export interface MutationDeleteParams {
   "@id": string;
}

export interface ApiContextType {
   usePrefetch: UsePrefetchHook;
   useGetItem: UseGetItemHook;
   useGetCollection: UseGetCollectionHook;
   useGetCollectionPaginated: UseGetCollectionPaginatedHook;
   usePatch: UsePatchHook;
   usePut: UsePutHook;
   usePost: UsePostHook;
   useDelete: UseDeleteHook;
   useInvalidation: UseInvalidationHook;
}

// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
const ApiContext = React.createContext<ApiContextType>(null!);

/**
 * A React component that provides access to API methods and values through the ApiProvider.
 *
 * @param {Object} props - The props for the component.
 * @param {string} props.baseUrl - The base URL of the API.
 * @param {AuthContextType} [props.auth] - The authentication token for API requests.
 * @param {QueryClient} props.client - The QueryClient instance.
 * @param {React.ReactNode} props.children - The child components.
 * @returns {ReactElement} The rendered child components wrapped in the ApiProvider.Provider.
 */
export function ApiProvider({
   baseUrl,
   auth,
   client,
   children,
}: {
   baseUrl: string;
   auth?: AuthContextType;
   client: QueryClient;
   children: React.ReactNode;
}): ReactElement {
   const providerValues = useMemo(() => {
      // --- Init fetch options ---
      let fetchOptions: RequestInit = {
         credentials: "include",
         headers: {
            Accept: ApiContentType,
            //  Authorization: `Bearer ${auth?.token}`,
            "Content-Type": ApiContentType,
         },
      };

      if (auth?.impersonate) {
         fetchOptions = {
            ...fetchOptions,
            headers: {
               ...fetchOptions.headers,
               "X-Switch-User": auth.impersonate,
            },
         };
      }

      return {
         /**
          * Function used to retrieve an item from a specific API path.
          *
          * @param {Object} options - The options for retrieving the item.
          * @param {string} options.path - The API path to retrieve the item from.
          * @param {string} options.id - The ID of the item to retrieve.
          * @param {boolean} [options.enabled] - Whether the item is currently enabled.
          *
          * @returns {Promise} A promise that resolves with the retrieved item.
          */
         useGetItem: <P extends Path>(options: {
            path: P;
            url?: string;
            enabled?: boolean;
         }): UseQueryResult<ApiPathMethodResponse<P, "get">> => {
            return useGetItem(baseUrl, fetchOptions, options);
         },

         /**
          * Retrieve a collection of data from the API using the GET method.
          *
          * @template Path - The type of API path to retrieve data from.
          * @template Options - The type of query options for the GET request.
          * @param {Object} options - The options for retrieving the collection.
          * @param {Path} options.path - The path to retrieve the collection from.
          * @param {Options} [options.options] - The query options for the GET request.
          * @param {boolean} [options.enabled] - Whether the GET request is enabled or not.
          * @returns {UseQueryResult<PaginateResult<ApiPathMethodResponse<Path, "get">>>} - The result of retrieving the collection.
          *
          * @example
          * const { data, isLoading, error } = useApi().useGetCollection({
          *   path: "/evenements",
          *   options: {
          *   "debut[after]": date.startOf("month").toISOString(),
          *   "fin[before]": date.endOf("month").toISOString(),
          *   },
          *   });
          */
         useGetCollection: <P extends Path>(options: {
            path: P;
            query?: ApiPathMethodQuery<P, "get">;
            parameters?: ApiPathMethodParameters<P, "get">;
            enabled?: boolean;
            onError?: (error: IErreurNotification) => void;
         }): UseQueryResult<PaginateResult<ApiPathMethodResponse<P, "get">>> => {
            return useGetCollection(baseUrl, fetchOptions, {
               path: options.path,
               query: options.query,
               parameters: options.parameters,
               enabled: options.enabled,
               onError: options.onError,
            });
         },

         /**
          * Retrieve a paginated collection from the API using GET method.
          *
          * @template Path - The API path of the collection.
          * @template Options - Additional query options for the API request.
          * @param {object} options - Options for retrieving the paginated collection.
          *    @param {Path} options.path - The API path of the collection.
          *    @param {number} [options.page] - The page number to retrieve (default is 1).
          *    @param {number} [options.itemsPerPage] - The number of items to retrieve per page (default is NB_MAX_ITEMS_PER_PAGE).
          *    @param {Options} [options.options] - Additional query options for the API request.
          *    @param {boolean} [options.enabled] - Whether the query should be enabled or not.
          * @returns {UseQueryResult<PaginateResult<ApiPathMethodResponse<Path, "get">>>} - The result of the paginated collection query.
          */
         useGetCollectionPaginated: <P extends Path>(options: {
            path: P;
            page?: number;
            itemsPerPage?: number;
            query?: ApiPathMethodQuery<P, "get">;
            parameters?: ApiPathMethodParameters<P, "get">;
            enabled?: boolean;
            onError?: (error: IErreurNotification) => void;
         }): UseQueryResult<PaginateResult<ApiPathMethodResponse<P, "get">>> => {
            return useGetCollection(baseUrl, fetchOptions, {
               path: options.path,
               // @ts-ignore
               query: {
                  ...options.query,
                  page: options.page,
                  itemsPerPage: options.itemsPerPage || NB_MAX_ITEMS_PER_PAGE,
               },
               parameters: options.parameters,
               enabled: options.enabled,
               onError: options.onError,
            });
         },

         /**
          * Executes a PATCH request to the specified API endpoint.
          *
          * @param {Object} options - The options for the PATCH request.
          * @param {string} options.path - The path of the API endpoint.
          * @param {Array} [options.invalidationQueryKeys] - Optional array of APIPaths to invalidate query caches.
          * @param {Function} [options.onSuccess] - Optional callback function to be executed on success.
          * @returns {Object} - The result of the PATCH request.
          */
         usePatch: <P extends Path>(options: {
            path: P;
            invalidationQueryKeys?: string[];
            onSuccess?: (
               data: ApiPathMethodResponse<P, "patch">,
               variables: MutationPatchParams<P>,
            ) => void;
         }): UseMutationResult<
            ApiPathMethodResponse<P, "patch">,
            unknown,
            MutationPatchParams<P>
         > => {
            return usePatch(baseUrl, fetchOptions, client, options);
         },

         /**
          * Executes a PUT request to the specified API endpoint.
          *
          * @param {Object} options - The options for the PUT request.
          * @param {string} options.path - The path of the API endpoint.
          * @param {Array} [options.invalidationQueryKeys] - Optional array of APIPaths to invalidate query caches.
          * @param {Function} [options.onSuccess] - Optional callback function to be executed on success.
          * @returns {Object} - The result of the PUT request.
          */
         usePut: <P extends Path>(options: {
            path: P;
            invalidationQueryKeys?: string[];
            onSuccess?: (
               data: ApiPathMethodResponse<P, "put">,
               variables: MutationPutParams<P>,
            ) => void;
         }): UseMutationResult<ApiPathMethodResponse<P, "put">, unknown, MutationPutParams<P>> => {
            return usePut(baseUrl, fetchOptions, client, options);
         },

         /**
          * Sends a POST request to the specified API path and returns the result using the useMutation hook.
          *
          * @param {Object} options - The options object for the usePost function.
          * @param {string} options.path - The API path to send the POST request to.
          * @param {Path[]} [options.invalidationQueryKeys] - An optional array of API paths that should trigger cache invalidation when the response is received.
          * @param {function} [options.onSuccess] - An optional callback function to be executed when the request is successful.
          * @returns {UseMutationResult} - The useMutation result object.
          */
         usePost: <P extends Path>(options: {
            path: P;
            url?: string;
            invalidationQueryKeys?: string[];
            onSuccess?: (
               data: ApiPathMethodResponse<P, "post">,
               variables: MutationPostParams<P>,
            ) => void;
         }): UseMutationResult<
            ApiPathMethodResponse<P, "post">,
            unknown,
            MutationPostParams<P>
         > => {
            return usePost(baseUrl, fetchOptions, client, options);
         },

         /**
          * Executes a DELETE request to the specified path with optional invalidation query keys and success callback.
          *
          * @template Path - The type of the API path.
          * @param {object} options - The options for the DELETE request.
          * @param {Path} options.path - The path for the DELETE request.
          * @param {string[]} [options.invalidationQueryKeys] - The optional invalidation query keys.
          * @param {function} [options.onSuccess] - The optional success callback function.
          * @returns {UseMutationResult<PaginateResult<ApiPathMethodResponse<Path, "get">>>} - The mutation result object.
          */
         useDelete: <P extends Path>(options: {
            path: P;
            invalidationQueryKeys?: string[];
            onSuccess?: (variables: MutationDeleteParams) => void;
         }): UseMutationResult<
            ApiPathMethodResponse<P, "delete">,
            unknown,
            MutationDeleteParams
         > => {
            return useDelete(baseUrl, fetchOptions, client, options);
         },

         /**
          * Performs invalidation for the specified query keys using the client.
          *
          * @param {string[]} queryKeys - The query keys to invalidate.
          * @param {VoidFunction} onSuccess - The function to be called upon successful invalidation.
          * @return {void}
          */
         useInvalidation: (queryKeys: string[], onSuccess?: VoidFunction): void =>
            handleInvalidation(client, queryKeys, onSuccess),

         /**
          * Performs pre-fetching of API data.
          *
          * @returns {Promise<void>} A Promise that resolves when the pre-fetching is complete.
          * @param options
          */
         usePrefetch: <P extends Path>(options: {
            path: P;
            query?: ApiPathMethodQuery<P, "get">;
            parameters?: ApiPathMethodParameters<P, "get">;
         }): Promise<void> => {
            return usePrefetch(baseUrl, fetchOptions, client, options);
         },
      };
   }, [baseUrl, client, auth]);

   return <ApiContext.Provider value={providerValues}>{children}</ApiContext.Provider>;
}

/**
 * Retrieves the API context for the application.
 * Contains methods to retrieve and manage data from the API.
 *
 * @returns {ApiContextType} The API context.
 */
export function useApi(): ApiContextType {
   return React.useContext(ApiContext);
}
