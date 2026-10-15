import { PaginateResult, useApi } from "@context/api/ApiProvider";
import React, { useEffect, useRef, useState } from "react";
import { Button, Progress } from "antd";
import {
  ApiPathMethodParameters,
  ApiPathMethodQuery,
  ApiPathMethodResponse,
  PaginatedPath,
} from "@api";
import { CSVLink } from "react-csv";
import { ExportOutlined } from "@ant-design/icons";
import { env } from "@/env";

type FetchItems<P extends PaginatedPath> = PaginateResult<ApiPathMethodResponse<P, "get">>["items"];

export interface CsvExportButtonWithFetchProps<P extends PaginatedPath, T extends object = object> {
  path: P;
  itemsPerPage: number;
  query?: ApiPathMethodQuery<P, "get">;
  parameters?: ApiPathMethodParameters<P, "get">;
  /** En-têtes CSV : tableau statique ou fonction calculée après récupération complète */
  headers:
    { label: string; key: string }[] | ((items: FetchItems<P>) => { label: string; key: string }[]);
  filename: string;
  getData: (items: FetchItems<P>) => T[];
  /** Quand false, diffère le téléchargement jusqu'à ce que les données de référence soient prêtes */
  ready?: boolean;
  onStart?: () => void;
  /** Si fourni, appelé au clic à la place du démarrage direct : l'export ne démarre que si `demarrer` est appelé. */
  onBeforeStart?: (demarrer: () => void) => void;
  onDownloaded?: () => void;
  icon?: React.ReactNode;
  label?: React.ReactNode;
}

// Pour des exports sur plusieurs endpoints ou demandant de retravailler les données, utiliser CsvExportButton.
export default function CsvExportButtonWithFetch<
  P extends PaginatedPath,
  T extends object = object,
>({
  path,
  itemsPerPage,
  query,
  parameters,
  headers,
  filename,
  getData,
  ready = true,
  onStart,
  onBeforeStart,
  onDownloaded,
  icon = <ExportOutlined />,
  label = "Exporter",
}: CsvExportButtonWithFetchProps<P, T>) {
  const [enabled, setEnabled] = useState(false);
  const [downloaded, setDownloaded] = useState(false);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const refDownload = useRef<any>(null);

  const { data, isError, isSuccess, fetchedCount, totalItems } = useApi().useGetFullCollection({
    path,
    itemsPerPage,
    query,
    parameters,
    enabled,
  });

  useEffect(() => {
    if (refDownload.current && isSuccess && ready && !downloaded) {
      refDownload.current.link.click();
    }
  });

  if (!enabled) {
    return (
      <Button
        icon={icon}
        onClick={() => {
          const demarrer = () => {
            setEnabled(true);
            onStart?.();
          };
          if (onBeforeStart) onBeforeStart(demarrer);
          else demarrer();
        }}
      >
        {label}
      </Button>
    );
  }

  if (!isSuccess) {
    return (
      <Progress
        style={{ width: "40vw" }}
        status={isError ? "exception" : undefined}
        percent={totalItems > 0 ? Math.min(100, Math.round((fetchedCount / totalItems) * 100)) : 0}
      />
    );
  }

  if (!ready) {
    return (
      <Button type="text" icon={<ExportOutlined />} loading disabled>
        Préparation des données...
      </Button>
    );
  }

  const csvHeaders =
    typeof headers === "function" ? headers(data!.items as FetchItems<P>) : headers;

  return (
    <CSVLink
      ref={refDownload}
      data={data ? getData(data.items as FetchItems<P>) : []}
      headers={csvHeaders}
      filename={`${env.REACT_APP_TITRE}-${filename}.csv`}
      separator=";"
      enclosingCharacter='"'
      onClick={() => {
        setDownloaded(true);
        onDownloaded?.();
        // Retour au bouton initial une fois le téléchargement lancé (hors du clic, pour ne pas
        // démonter le lien avant le téléchargement) : un nouvel export repasse par onBeforeStart.
        setTimeout(() => {
          setEnabled(false);
          setDownloaded(false);
        }, 0);
      }}
      aria-label="Exporter le tableau au format CSV (Excel)"
    >
      <Button icon={<ExportOutlined />}>Exporter</Button>
    </CSVLink>
  );
}
