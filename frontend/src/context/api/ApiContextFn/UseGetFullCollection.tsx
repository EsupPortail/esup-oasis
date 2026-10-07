/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useQueries, useQuery } from "@tanstack/react-query";
import { useMemo, useRef } from "react";
import { useNavigate } from "react-router";
import { PaginateResult, RequestMethod } from "@context/api/ApiProvider";
import {
  ApiPathMethodParameters,
  ApiPathMethodQuery,
  ApiPathMethodResponse,
  PaginatedPath,
} from "@api";
import { buildUrl } from "@context/api/ApiContextFn/UrlBuilder";
import {
  handleApiResponse,
  IErreurNotification,
} from "@context/api/ApiContextFn/HandleApiResponse";
import { useAuth } from "@/auth/AuthProvider";

const DEFAULT_ITEMS_PER_PAGE = 200;
const DEFAULT_CONCURRENCY = 5;

export type UseGetFullCollectionHook = <P extends PaginatedPath>(options: {
  path: P;
  query?: ApiPathMethodQuery<P, "get">;
  enabled?: boolean;
  parameters?: ApiPathMethodParameters<P, "get">;
  onError?: (error: IErreurNotification) => void;
  itemsPerPage?: number;
  concurrency?: number;
}) => {
  data: PaginateResult<ApiPathMethodResponse<P, "get">> | undefined;
  fetchedCount: number;
  totalItems: number;
  isLoading: boolean;
  isLoadingPage1: boolean;
  isFetching: boolean;
  isError: boolean;
  isSuccess: boolean;
};

export function useGetFullCollection<P extends PaginatedPath>(
  baseUrl: string,
  fetchOptions: RequestInit,
  options: {
    path: P;
    query?: ApiPathMethodQuery<P, "get">;
    enabled?: boolean;
    parameters?: ApiPathMethodParameters<P, "get">;
    onError?: (error: IErreurNotification) => void;
    itemsPerPage?: number;
    concurrency?: number;
  },
) {
  const PAGE_SIZE = options.itemsPerPage ?? DEFAULT_ITEMS_PER_PAGE;
  const CONCURRENCY = options.concurrency ?? DEFAULT_CONCURRENCY;
  const navigate = useNavigate();
  const auth = useAuth();
  const isEnabled = options.enabled !== false;

  // Résultats du rendu précédent pour piloter le déclenchement par tranche (sliding window)
  const prevResultsRef = useRef<Array<{ isSuccess: boolean; isError: boolean }>>([]);

  const buildPageUrl = (page: number): URL =>
    buildUrl<P, "get">(baseUrl, options.path, undefined, options.parameters, {
      ...options.query,
      page,
      itemsPerPage: PAGE_SIZE,
    } as ApiPathMethodQuery<P, "get">);

  const makeFetchFn = (page: number) => async () => {
    const url = buildPageUrl(page);
    const data = await handleApiResponse(
      RequestMethod.GET,
      await fetch(url, { method: "GET", ...fetchOptions }),
      navigate,
      auth,
      options,
      undefined,
      options.onError,
    );
    return {
      items: data["hydra:member"],
      totalItems: data["hydra:totalItems"],
      currentPage: page,
      itemsPerPage: PAGE_SIZE,
    } as PaginateResult<ApiPathMethodResponse<P, "get">>;
  };

  const page1Url = buildPageUrl(1);
  const page1 = useQuery({
    queryKey: [options.path, page1Url.toString(), auth.user?.uid],
    queryFn: makeFetchFn(1),
    enabled: isEnabled && Boolean(page1Url),
  });

  const totalItems = page1.data?.totalItems ?? 0;
  const totalPages = totalItems > 0 ? Math.ceil(totalItems / PAGE_SIZE) : 0;
  const remainingPageNumbers = Array.from({ length: Math.max(0, totalPages - 1) }, (_, i) => i + 2);

  const remainingQueries = useQueries({
    queries: remainingPageNumbers.map((page, idx) => {
      // La requête à l'index idx ne démarre que lorsque la requête à (idx - CONCURRENCY)
      // est terminée, ce qui crée une fenêtre glissante de CONCURRENCY requêtes simultanées.
      const prerequisiteIdx = idx - CONCURRENCY;
      const prerequisiteDone =
        prerequisiteIdx < 0 ||
        prevResultsRef.current[prerequisiteIdx]?.isSuccess ||
        prevResultsRef.current[prerequisiteIdx]?.isError;

      const url = buildPageUrl(page);
      return {
        queryKey: [options.path, url.toString(), auth.user?.uid],
        queryFn: makeFetchFn(page),
        enabled: isEnabled && !!page1.data && prerequisiteDone,
      };
    }),
  });

  // Mise à jour de la ref après chaque rendu pour le prochain cycle
  prevResultsRef.current = remainingQueries.map((q) => ({
    isSuccess: q.isSuccess,
    isError: q.isError,
  }));

  const page1Items = (page1.data?.items ?? []) as unknown[];
  const remainingItems = remainingQueries.flatMap((q) => (q.data?.items ?? []) as unknown[]);

  const fetchedCount =
    page1Items.length +
    remainingQueries.reduce((sum, q) => sum + ((q.data?.items as unknown[])?.length ?? 0), 0);

  // allDone est vrai quand toutes les pages sont chargées, OU quand le nombre d'items récupérés
  // atteint le total déclaré par l'API — cas où l'API renvoie plus d'items que itemsPerPage sur
  // une seule page (hydra:totalItems incohérent) ou quand la fenêtre glissante ne peut pas avancer.
  const allDone =
    page1.isSuccess &&
    (totalPages <= 1 ||
      remainingQueries.every((q) => q.isSuccess || q.isError) ||
      (totalItems > 0 && fetchedCount >= totalItems));

  // Memoize data via timestamps pour éviter une nouvelle référence à chaque rendu
  // (sinon les useEffect qui dépendent de `data` boucleraient infiniment)
  const dataTimestamps = [
    page1.dataUpdatedAt,
    ...remainingQueries.map((q) => q.dataUpdatedAt),
  ].join("-");

  const stableData = useMemo(() => {
    if (!allDone) return undefined;
    const allItems = [...page1Items, ...remainingItems] as PaginateResult<
      ApiPathMethodResponse<P, "get">
    >["items"];
    return {
      items: allItems,
      totalItems,
      currentPage: 1,
      itemsPerPage: PAGE_SIZE,
    } as PaginateResult<ApiPathMethodResponse<P, "get">>;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [allDone, dataTimestamps, totalItems]);

  return {
    data: stableData,
    fetchedCount,
    totalItems,
    isLoading: page1.isLoading || remainingQueries.some((q) => q.isLoading),
    isFetching: page1.isFetching || remainingQueries.some((q) => q.isFetching),
    isLoadingPage1: page1.isLoading,
    isError: page1.isError || remainingQueries.some((q) => q.isError),
    isSuccess: allDone,
  };
}
