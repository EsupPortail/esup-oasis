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
import { NB_MAX_ITEMS_PER_PAGE } from "@/constants";
import {
  handleInvalidation,
  UseInvalidationHook,
} from "@context/api/ApiContextFn/HandleInvalidation";
import { useGetItem, UseGetItemHook } from "@context/api/ApiContextFn/UseGetItem";
import {
  useGetCollection,
  UseGetCollectionHook,
  UseGetCollectionPaginatedHook,
} from "@context/api/ApiContextFn/UseGetCollection";
import {
  useGetFullCollection,
  UseGetFullCollectionHook,
} from "@context/api/ApiContextFn/UseGetFullCollection";
import { usePatch, UsePatchHook } from "@context/api/ApiContextFn/UsePatch";
import { usePost, UsePostHook } from "@context/api/ApiContextFn/UsePost";
import { useDelete, UseDeleteHook } from "@context/api/ApiContextFn/UseDelete";
import { AuthContextType } from "@/auth/AuthProvider";
import {
  ApiContentType,
  ApiPathMethodParameters,
  ApiPathMethodQuery,
  ApiPathMethodRequestBody,
  ApiPathMethodResponse,
  ContentTypePatch,
  PaginatedPath,
  Path,
} from "@api";
import { usePut, UsePutHook } from "@context/api/ApiContextFn/UsePut";
import { IErreurNotification } from "@context/api/ApiContextFn/HandleApiResponse";

export enum RequestMethod {
  GET = "GET",
  POST = "POST",
  DELETE = "DELETE",
  PATCH = "PATCH",
  PUT = "PUT",
}

/** Résultat normalisé d'un appel `useGetCollection` ou `useGetCollectionPaginated`. `items` contient les membres Hydra (`hydra:member`). */
export interface PaginateResult<T> {
  items: "hydra:member" extends keyof T ? T["hydra:member"] : T;
  totalItems: number;
  currentPage: number;
  itemsPerPage: number;
}

/** Paramètres d'appel de `mutation.mutate()` pour `usePatch`. `"@id"` est l'IRI de la ressource (ex. `/evenements/42`), `data` le corps partiel au format `merge-patch+json`. */
export interface MutationPatchParams<P extends Path> {
  data: ApiPathMethodRequestBody<P, "patch", ContentTypePatch>;
  "@id": string;
}

/** Paramètres d'appel de `mutation.mutate()` pour `usePut`. `"@id"` est l'IRI de la ressource, `data` le corps complet JSON. */
export interface MutationPutParams<P extends Path> {
  data: ApiPathMethodRequestBody<P, "put">;
  "@id": string;
}

/** Paramètres d'appel de `mutation.mutate()` pour `usePost`. `data` est le corps JSON envoyé au serveur. */
export interface MutationPostParams<P extends Path> {
  data: ApiPathMethodRequestBody<P, "post">;
}

/** Paramètres d'appel de `mutation.mutate()` pour `useDelete`. `"@id"` est l'IRI de la ressource à supprimer. */
export interface MutationDeleteParams {
  "@id": string;
}

export interface ApiContextType {
  useGetItem: UseGetItemHook;
  useGetCollection: UseGetCollectionHook;
  useGetCollectionPaginated: UseGetCollectionPaginatedHook;
  useGetFullCollection: UseGetFullCollectionHook;
  usePatch: UsePatchHook;
  usePut: UsePutHook;
  usePost: UsePostHook;
  useDelete: UseDeleteHook;
  useInvalidation: UseInvalidationHook;
}

const ApiContext = React.createContext<ApiContextType>(null!);

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
    let fetchOptions: RequestInit = {
      credentials: "include",
      headers: {
        Accept: ApiContentType,
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
      useGetItem: <P extends Path>(options: {
        path: P;
        url?: string;
        enabled?: boolean;
        onError?: (error: IErreurNotification) => void;
      }): UseQueryResult<ApiPathMethodResponse<P, "get">> => {
        return useGetItem(baseUrl, fetchOptions, options);
      },

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
          query: {
            ...options.query,
            page: options.page,
            itemsPerPage: options.itemsPerPage || NB_MAX_ITEMS_PER_PAGE,
          } as ApiPathMethodQuery<P, "get">,
          parameters: options.parameters,
          enabled: options.enabled,
          onError: options.onError,
        });
      },

      useGetFullCollection: <P extends PaginatedPath>(options: {
        path: P;
        query?: ApiPathMethodQuery<P, "get">;
        enabled?: boolean;
        parameters?: ApiPathMethodParameters<P, "get">;
        onError?: (error: IErreurNotification) => void;
        itemsPerPage?: number;
        concurrency?: number;
      }) => {
        return useGetFullCollection(baseUrl, fetchOptions, options);
      },

      usePatch: <P extends Path>(options: {
        path: P;
        invalidationQueryKeys?: string[];
        onSuccess?: (
          data: ApiPathMethodResponse<P, "patch">,
          variables: MutationPatchParams<P>,
        ) => void;
      }): UseMutationResult<ApiPathMethodResponse<P, "patch">, unknown, MutationPatchParams<P>> => {
        return usePatch(baseUrl, fetchOptions, client, options);
      },

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

      usePost: <P extends Path>(options: {
        path: P;
        url?: string;
        invalidationQueryKeys?: string[];
        onSuccess?: (
          data: ApiPathMethodResponse<P, "post">,
          variables: MutationPostParams<P>,
        ) => void;
      }): UseMutationResult<ApiPathMethodResponse<P, "post">, unknown, MutationPostParams<P>> => {
        return usePost(baseUrl, fetchOptions, client, options);
      },

      useDelete: <P extends Path>(options: {
        path: P;
        invalidationQueryKeys?: string[];
        onSuccess?: (variables: MutationDeleteParams) => void;
      }): UseMutationResult<ApiPathMethodResponse<P, "delete">, unknown, MutationDeleteParams> => {
        return useDelete(baseUrl, fetchOptions, client, options);
      },

      useInvalidation: (queryKeys: string[], onSuccess?: VoidFunction): void =>
        handleInvalidation(client, queryKeys, onSuccess),
    };
  }, [baseUrl, client, auth]);

  return <ApiContext.Provider value={providerValues}>{children}</ApiContext.Provider>;
}

export function useApi(): ApiContextType {
  const ctx = React.useContext(ApiContext);
  if (ctx === null) throw new Error("useApi doit être utilisé dans un <ApiProvider>");
  return ctx;
}
