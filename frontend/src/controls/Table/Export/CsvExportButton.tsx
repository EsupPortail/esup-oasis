/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { PaginatedPath } from "@api";
import CsvExportButtonWithoutFetch, {
  CsvExportButtonWithoutFetchProps,
} from "./CsvExportButtonWithoutFetch";
import CsvExportButtonWithFetch, {
  CsvExportButtonWithFetchProps,
} from "./CsvExportButtonWithFetch";

/*
Wrapper unifié pour les exports CSV.
- Avec `path` : délègue à CsvExportButtonWithFetch (fetch paginé intégré, endpoint unique).
- Sans `path` : délègue à CsvExportButton (données préparées par l'appelant).
*/
type CsvExportButtonProps<P extends PaginatedPath, T extends object = object> =
  ({ path: P } & CsvExportButtonWithFetchProps<P, T>) | CsvExportButtonWithoutFetchProps<T>;

export default function CsvExportButton<P extends PaginatedPath, T extends object = object>(
  props: CsvExportButtonProps<P, T>,
) {
  if ("path" in props) {
    return <CsvExportButtonWithFetch {...(props as CsvExportButtonWithFetchProps<P, T>)} />;
  }
  return <CsvExportButtonWithoutFetch {...(props as CsvExportButtonWithoutFetchProps<T>)} />;
}
