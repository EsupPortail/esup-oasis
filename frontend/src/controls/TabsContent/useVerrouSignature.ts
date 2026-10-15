/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { useApi } from "@context/api/ApiProvider";
import { intervalleSuiviEnvoi, verrouSignature } from "@lib";

/** Motif du verrou de signature pour un bénéficiaire, `undefined` s'il peut être modifié. */
export function useVerrouSignature(utilisateurId?: string): string | undefined {
  const { data: utilisateur } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: utilisateurId,
    enabled: !!utilisateurId,
    refetchInterval: intervalleSuiviEnvoi,
  });

  return verrouSignature(utilisateur);
}
