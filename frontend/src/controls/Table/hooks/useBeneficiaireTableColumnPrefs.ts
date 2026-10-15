/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  BENEFICIAIRE_TABLE_COLUMNS_KEYS,
  getBeneficiaireTableColumnOptions,
  getBeneficiaireTableInitialColumns,
  TableColumnOption,
} from "@controls/Table/BeneficiaireTableColumns";
import { usePreferences } from "@context/utilisateurPreferences/UtilisateurPreferencesProvider";

export const STORAGE_KEY_COLONNES_BENEFICIAIRES = "oasis:table:beneficiaires:colonnes";
export const PREF_KEY_COLONNES_BENEFICIAIRES = "colonnesBeneficiaires";

interface ColumnPreferences {
  ordre: string[];
  visibles: string[];
}

const STANDARD_KEYS = new Set<string>(Object.values(BENEFICIAIRE_TABLE_COLUMNS_KEYS));

/**
 * @param complementairesChargees tant que les colonnes complémentaires ne sont pas connues, on conserve
 * les clés non standard stockées (sinon elles seraient perdues avant même d'avoir pu être validées).
 */
function sanitizeColumnPreferences(
  availableKeys: string[],
  initialKeys: string[],
  complementairesChargees: boolean,
  stored?: Partial<ColumnPreferences> | null,
): ColumnPreferences {
  const nom = BENEFICIAIRE_TABLE_COLUMNS_KEYS.NOM;
  const actions = BENEFICIAIRE_TABLE_COLUMNS_KEYS.ACTIONS;
  const hasActions = availableKeys.includes(actions);

  const isKept = (k: string) =>
    availableKeys.includes(k) || (!complementairesChargees && !STANDARD_KEYS.has(k));

  const defaultOrder = [
    ...initialKeys.filter((k) => k !== actions),
    ...availableKeys.filter((k) => !initialKeys.includes(k) && k !== actions),
  ];

  const middleKeys = (stored?.ordre || defaultOrder).filter(
    (k) => isKept(k) && k !== nom && k !== actions,
  );
  availableKeys.forEach((k) => {
    if (k !== nom && k !== actions && !middleKeys.includes(k)) {
      middleKeys.push(k);
    }
  });

  const ordre = [nom, ...middleKeys, ...(hasActions ? [actions] : [])];
  const visibles = [
    nom,
    ...(stored?.visibles || initialKeys).filter((k) => isKept(k) && k !== nom),
  ];

  return { ordre, visibles };
}

function toStringArray(value: unknown): string[] | undefined {
  return Array.isArray(value) ? value.filter((v): v is string => typeof v === "string") : undefined;
}

/** Accepte l'ancien format (simple tableau de colonnes visibles) et le format { ordre, visibles }. */
function toStoredPreferences(raw: unknown): Partial<ColumnPreferences> | null {
  if (Array.isArray(raw)) {
    const keys = toStringArray(raw);
    return keys?.length ? { ordre: keys, visibles: keys } : null;
  }
  if (!raw || typeof raw !== "object") return null;
  const { ordre, visibles } = raw as Record<string, unknown>;
  const stored = { ordre: toStringArray(ordre), visibles: toStringArray(visibles) };
  return stored.ordre || stored.visibles ? stored : null;
}

function parseLocalStorage(): Partial<ColumnPreferences> | null {
  try {
    const stored = localStorage.getItem(STORAGE_KEY_COLONNES_BENEFICIAIRES);
    return stored ? toStoredPreferences(JSON.parse(stored)) : null;
  } catch {
    return null;
  }
}

function saveLocalStorage(prefs: ColumnPreferences) {
  try {
    localStorage.setItem(STORAGE_KEY_COLONNES_BENEFICIAIRES, JSON.stringify(prefs));
  } catch {
    /* ignore */
  }
}

/**
 * @param colonnesComplementaires `undefined` tant que les colonnes complémentaires ne sont pas chargées
 */
export function useBeneficiaireTableColumnPrefs(
  isGestionnaire?: boolean,
  colonnesComplementaires?: string[],
) {
  const { getPreferenceJson, setPreferenceJson, preferencesChargees } = usePreferences();
  const complementairesChargees = colonnesComplementaires !== undefined;

  const initialColumns = useMemo(
    () => getBeneficiaireTableInitialColumns(isGestionnaire),
    [isGestionnaire],
  );

  const colonnesDisponibles = useMemo(
    () => getBeneficiaireTableColumnOptions(isGestionnaire, colonnesComplementaires),
    [isGestionnaire, colonnesComplementaires],
  );

  const availableKeys = useMemo(() => colonnesDisponibles.map((c) => c.key), [colonnesDisponibles]);

  const hadCustomColumns = useRef(false);

  const [columnPrefs, setColumnPrefs] = useState<ColumnPreferences>(() => {
    const stored = parseLocalStorage();
    hadCustomColumns.current = !!stored;
    return sanitizeColumnPreferences(
      availableKeys,
      initialColumns,
      complementairesChargees,
      stored,
    );
  });

  const serverPrefsLoaded = useRef(false);

  // Synchronisation lors du chargement des préférences utilisateur
  useEffect(() => {
    if (!preferencesChargees || serverPrefsLoaded.current) return;
    serverPrefsLoaded.current = true;

    const serverPrefs = toStoredPreferences(getPreferenceJson?.(PREF_KEY_COLONNES_BENEFICIAIRES));

    if (serverPrefs) {
      hadCustomColumns.current = true;
      const sanitized = sanitizeColumnPreferences(
        availableKeys,
        initialColumns,
        complementairesChargees,
        serverPrefs,
      );
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setColumnPrefs(sanitized);
      saveLocalStorage(sanitized);
    } else if (hadCustomColumns.current) {
      setPreferenceJson?.(PREF_KEY_COLONNES_BENEFICIAIRES, columnPrefs);
    }
  }, [
    preferencesChargees,
    availableKeys,
    initialColumns,
    complementairesChargees,
    getPreferenceJson,
    setPreferenceJson,
    columnPrefs,
  ]);

  // Si les colonnes disponibles ou initiales changent, synchroniser avec availableKeys
  useEffect(() => {
    setColumnPrefs((prev) => {
      const sanitized = sanitizeColumnPreferences(
        availableKeys,
        initialColumns,
        complementairesChargees,
        hadCustomColumns.current ? prev : null,
      );
      const unchanged =
        sanitized.ordre.join(",") === prev.ordre.join(",") &&
        sanitized.visibles.join(",") === prev.visibles.join(",");
      return unchanged ? prev : sanitized;
    });
  }, [availableKeys, initialColumns, complementairesChargees]);

  const updateColumnPrefs = useCallback(
    (newPrefs: ColumnPreferences) => {
      hadCustomColumns.current = true;
      setColumnPrefs(newPrefs);
      saveLocalStorage(newPrefs);
      setPreferenceJson?.(PREF_KEY_COLONNES_BENEFICIAIRES, newPrefs);
    },
    [setPreferenceJson],
  );

  const handleChangeColonnesVisibles = useCallback(
    (visibles: string[]) => {
      const nom = BENEFICIAIRE_TABLE_COLUMNS_KEYS.NOM;
      const ensured = visibles.includes(nom) ? visibles : [nom, ...visibles];
      updateColumnPrefs({ ...columnPrefs, visibles: ensured });
    },
    [columnPrefs, updateColumnPrefs],
  );

  const handleReorderColonnes = useCallback(
    (nouvelOrdre: string[]) => {
      updateColumnPrefs({ ...columnPrefs, ordre: nouvelOrdre });
    },
    [columnPrefs, updateColumnPrefs],
  );

  const handleResetColonnes = useCallback(() => {
    updateColumnPrefs(
      sanitizeColumnPreferences(availableKeys, initialColumns, complementairesChargees),
    );
  }, [availableKeys, initialColumns, complementairesChargees, updateColumnPrefs]);

  // Seules les colonnes réellement disponibles sont exposées (les clés non encore validées sont masquées)
  const colonnesOrdonnees = useMemo(() => {
    const optionsMap = new Map(colonnesDisponibles.map((c) => [c.key, c]));
    return columnPrefs.ordre
      .map((key) => optionsMap.get(key))
      .filter((c): c is TableColumnOption => c !== undefined);
  }, [colonnesDisponibles, columnPrefs.ordre]);

  const colonnesAffichees = useMemo(
    () => colonnesOrdonnees.map((c) => c.key).filter((key) => columnPrefs.visibles.includes(key)),
    [colonnesOrdonnees, columnPrefs.visibles],
  );

  return {
    colonnesOrdonnees,
    colonnesAffichees,
    colonnesVisibles: columnPrefs.visibles,
    handleChangeColonnesVisibles,
    handleReorderColonnes,
    handleResetColonnes,
  };
}
