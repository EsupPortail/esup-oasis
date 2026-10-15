/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useEffect, useRef, useState } from "react";
import { Button, Progress } from "antd";
import { CSVLink } from "react-csv";
import { ExportOutlined } from "@ant-design/icons";
import { env } from "@/env";

export interface CsvExportButtonWithoutFetchProps<T extends object = object> {
  getData: () => T[];
  headers: { label: string; key: string }[];
  filename: string;
  /** Quand false, diffère le téléchargement jusqu'à ce que les données de référence soient prêtes */
  ready?: boolean;
  /** Progression optionnelle : nombre d'items déjà chargés */
  fetchedCount?: number;
  /** Progression optionnelle : nombre total d'items à charger */
  totalItems?: number;
  /** Appelé quand l'utilisateur clique sur le bouton d'export */
  onStart?: () => void;
  /** Si fourni, appelé au clic à la place du démarrage direct : l'export ne démarre que si `demarrer` est appelé. */
  onBeforeStart?: (demarrer: () => void) => void;
  onDownloaded?: () => void;
  icon?: React.ReactNode;
  label?: React.ReactNode;
}

/*
Ce composant permet d'exporter des données au format CSV. Il est utilisé quand la source de données est préparée et prête.
Pour des exports simples (un endpoint unique), il est conseillé d'utiliser CsvExportButtonWithFetch.
 */
export default function CsvExportButtonWithoutFetch<T extends object = object>({
  getData,
  headers,
  filename,
  ready = true,
  fetchedCount,
  totalItems,
  onStart,
  onBeforeStart,
  onDownloaded,
  icon = <ExportOutlined />,
  label = "Exporter",
}: CsvExportButtonWithoutFetchProps<T>) {
  const [started, setStarted] = useState(false);
  const [downloaded, setDownloaded] = useState(false);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const refDownload = useRef<any>(null);

  // Déclenche le téléchargement dès que started + ready sont réunis
  useEffect(() => {
    if (refDownload.current && started && ready && !downloaded) {
      refDownload.current.link.click();
    }
  });

  if (!started) {
    return (
      <Button
        icon={icon}
        onClick={() => {
          const demarrer = () => {
            setStarted(true);
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

  if (!ready) {
    if (totalItems && totalItems > 0) {
      return (
        <Progress
          style={{ width: "40vw" }}
          percent={Math.min(100, Math.round(((fetchedCount ?? 0) / totalItems) * 100))}
        />
      );
    }
    return (
      <Button type="text" icon={<ExportOutlined />} loading disabled>
        Préparation des données...
      </Button>
    );
  }

  return (
    <CSVLink
      ref={refDownload}
      data={getData()}
      headers={headers}
      filename={`${env.REACT_APP_TITRE}-${filename}.csv`}
      separator=";"
      enclosingCharacter='"'
      onClick={() => {
        setDownloaded(true);
        onDownloaded?.();
        // Retour au bouton initial une fois le téléchargement lancé (hors du clic, pour ne pas
        // démonter le lien avant le téléchargement) : un nouvel export repasse par onBeforeStart.
        setTimeout(() => {
          setStarted(false);
          setDownloaded(false);
        }, 0);
      }}
      aria-label="Exporter le tableau au format CSV (Excel)"
    >
      <Button icon={<ExportOutlined />}>Exporter</Button>
    </CSVLink>
  );
}
