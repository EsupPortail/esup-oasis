/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useMemo, useState } from "react";
import { useApi } from "@context/api/ApiProvider";
import { IParametre } from "@api";

const PREFIXE_MENU = "MENU_";

export interface UseLabelsMenuResult {
  /** Libellé courant de chaque paramètre MENU_*, surcharges locales non sauvegardées incluses. */
  labels: Record<string, string>;
  /** Paramètres MENU_* bruts, indexés par clé (nécessaires pour éditer une valeur existante). */
  parametresParCle: Map<string, IParametre>;
  isFetching: boolean;
  /** Reflète immédiatement une saisie en cours dans les libellés, avant sauvegarde côté API. */
  setSurcharge: (cle: string, valeur: string) => void;
}

/**
 * Libellés personnalisables du menu principal, partagés entre le menu réel de production
 * (`AppLayoutMenu`) et l'aperçu de la page d'administration (`MenuPreview`).
 */
export function useLabelsMenu(): UseLabelsMenuResult {
  const { data, isFetching } = useApi().useGetFullCollection({ path: "/parametres" });
  const [surcharges, setSurcharges] = useState<Record<string, string>>({});

  const parametresParCle = useMemo(() => {
    const map = new Map<string, IParametre>();
    (data?.items ?? []).forEach((parametre) => {
      const cle = parametre.cle;
      if (cle?.startsWith(PREFIXE_MENU)) map.set(cle, parametre);
    });
    return map;
  }, [data]);

  const labels = useMemo(() => {
    const valeursServeur: Record<string, string> = {};
    parametresParCle.forEach((parametre, cle) => {
      // N'ajoute la clé que si une valeur existe réellement : une chaîne vide neutraliserait
      // le fallback `labels?.MENU_XXX ?? "..."` des menuItemXxx (?? ne se déclenche que sur
      // null/undefined, pas sur une chaîne vide).
      const valeur = parametre.valeursCourantes?.[0]?.valeur;
      if (valeur) valeursServeur[cle] = valeur;
    });
    return { ...valeursServeur, ...surcharges };
  }, [parametresParCle, surcharges]);

  function setSurcharge(cle: string, valeur: string): void {
    setSurcharges((prev) => ({ ...prev, [cle]: valeur }));
  }

  return { labels, parametresParCle, isFetching, setSurcharge };
}
