/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { ThemeMode } from "@context/theme/ThemeContext";
import { Button, MenuProps } from "antd";
import { CheckOutlined, DesktopOutlined, MoonOutlined, SunOutlined } from "@ant-design/icons";
import React from "react";

function ThemeIcon({ themeMode }: { themeMode: ThemeMode }) {
  if (themeMode === "dark") return <MoonOutlined aria-hidden />;
  if (themeMode === "light") return <SunOutlined aria-hidden />;
  return <DesktopOutlined aria-hidden />;
}

export function menuItemTheme(
  themeMode: ThemeMode,
  setThemeMode: (value: ThemeMode) => void,
  setPreference: (key: string, value: string) => void,
  labels?: Record<string, string>,
): MenuProps["items"] {
  const onChange = (value: ThemeMode) => {
    setThemeMode(value);
    setPreference("theme-mode", value);
  };

  return [
    {
      key: "theme",
      title: "Thème de l'interface",
      className: "menu-small-item no-indicator item-theme",
      label: (
        <Button
          type="text"
          className="bg-transparent p-0"
          aria-label="Choisir le thème de l'interface"
        >
          <span className="show-on-overflow">{labels?.MENU_THEME ?? "Thème"}</span>
          <ThemeIcon themeMode={themeMode} />
        </Button>
      ),
      children: [
        {
          key: "theme-light",
          label: labels?.MENU_THEME_CLAIR ?? "Mode clair",
          icon: (
            <>
              <CheckOutlined
                aria-label={themeMode === "light" ? "Sélectionné" : "Non sélectionné"}
                className={themeMode === "light" ? "mr-1" : "mr-1 v-hidden"}
              />
              <SunOutlined className="mr-1" aria-hidden />
            </>
          ),
          onClick: () => onChange("light"),
        },
        {
          key: "theme-dark",
          label: labels?.MENU_THEME_SOMBRE ?? "Mode sombre",
          icon: (
            <>
              <CheckOutlined
                aria-label={themeMode === "dark" ? "Sélectionné" : "Non sélectionné"}
                className={themeMode === "dark" ? "mr-1" : "mr-1 v-hidden"}
              />
              <MoonOutlined className="mr-1" aria-hidden />
            </>
          ),
          onClick: () => onChange("dark"),
        },
        {
          key: "theme-system",
          label: labels?.MENU_THEME_SYSTEME ?? "Identique au système",
          icon: (
            <>
              <CheckOutlined
                aria-label={themeMode === "system" ? "Sélectionné" : "Non sélectionné"}
                className={themeMode === "system" ? "mr-1" : "mr-1 v-hidden"}
              />
              <DesktopOutlined className="mr-1" aria-hidden />
            </>
          ),
          onClick: () => onChange("system"),
        },
      ],
    },
  ];
}
