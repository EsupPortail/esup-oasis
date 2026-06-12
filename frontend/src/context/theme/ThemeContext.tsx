/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { createContext, useCallback, useContext, useState } from "react";

export type ThemeMode = "light" | "dark" | "system";

interface IThemeContext {
  themeMode: ThemeMode;
  setThemeMode: (value: ThemeMode) => void;
}

const ThemeContext = createContext<IThemeContext | undefined>(undefined);

const THEME_STORAGE_KEY = "theme-mode";

function readStoredTheme(): ThemeMode {
  const stored = localStorage.getItem(THEME_STORAGE_KEY);
  return (["light", "dark", "system"] as ThemeMode[]).includes(stored as ThemeMode)
    ? (stored as ThemeMode)
    : "system";
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const [themeMode, setThemeModeState] = useState<ThemeMode>(readStoredTheme);

  const setThemeMode = useCallback((value: ThemeMode) => {
    localStorage.setItem(THEME_STORAGE_KEY, value);
    setThemeModeState(value);
  }, []);

  return (
    <ThemeContext.Provider value={{ themeMode, setThemeMode }}>{children}</ThemeContext.Provider>
  );
}

export function useTheme(): IThemeContext {
  const ctx = useContext(ThemeContext);
  if (!ctx) throw new Error("useTheme must be used within ThemeProvider");
  return ctx;
}
