/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { PorteeExportInscriptions } from "@lib/inscription";

/** Colonnes d'inscriptions d'un export CSV (cf. `formaterInscriptionsExport`). */
export function getInscriptionsHeaders(
  portee: PorteeExportInscriptions,
): { label: string; key: string }[] {
  return [
    { label: "Composante", key: "composante" },
    { label: "Formation", key: "formation" },
    ...(portee === "principale"
      ? []
      : [{ label: "Autres inscriptions en cours", key: "autresInscriptionsEnCours" }]),
    ...(portee === "toutes"
      ? [{ label: "Autres inscriptions passées", key: "autresInscriptionsPassees" }]
      : []),
  ];
}
