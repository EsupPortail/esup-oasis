/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IAccessibilite } from "@context/accessibilite/AccessibiliteContext";
import { Button, MenuProps } from "antd";
import Icon, { CheckOutlined } from "@ant-design/icons";
import React from "react";
import IconeAccessibilite from "@/assets/images/accessibilite.svg?react";

export function menuItemAccessibilite(
  appAccessibilite: IAccessibilite,
  setContrast: (value: boolean) => void,
  setDyslexieArial: (value: boolean) => void,
  setDyslexieOpenDys: (value: boolean) => void,
  setDyslexieLexend: (value: boolean) => void,
  setPoliceLarge: (value: boolean) => void,
  setPreference: (key: string, value: string) => void,
  labels?: Record<string, string>,
): MenuProps["items"] {
  return [
    {
      key: "accessibilite",
      title: "Options d'accessibilité",
      className: "menu-small-item no-indicator item-accessibilite",
      label: (
        <Button
          type="text"
          className="bg-transparent p-0"
          aria-label="Ajuster les préférences d'accessibilité"
        >
          <span className="show-on-overflow">{labels?.MENU_A11Y ?? "Accessibilité"}</span>
          <Icon component={IconeAccessibilite} aria-hidden className="hide-on-overflow" />
        </Button>
      ),
      children: [
        {
          key: "accessibilite-contraste",
          label: labels?.MENU_A11Y_CONTRASTE ?? "Contraste",
          icon: (
            <CheckOutlined
              aria-label={
                appAccessibilite.contrast ? "Fonctionnalité activée" : "Fonctionnalité désactivée"
              }
              className={appAccessibilite.contrast ? "mr-1" : "mr-1 v-hidden"}
            />
          ),
          onClick: () => {
            const nextContrast = !appAccessibilite.contrast;
            setContrast(nextContrast);
            setPreference("contrast", nextContrast ? "true" : "false");
          },
        },
        {
          key: "divider-accessibilite",
          type: "divider",
        },
        {
          key: "accessibilite-dyslexie-lexend",
          label: labels?.MENU_A11Y_POLICE_LEXEND ?? "Police : Lexend",
          icon: (
            <CheckOutlined
              aria-label={
                appAccessibilite.dyslexieLexend
                  ? "Fonctionnalité activée"
                  : "Fonctionnalité désactivée"
              }
              className={appAccessibilite.dyslexieLexend ? "mr-1" : "mr-1 v-hidden"}
            />
          ),
          onClick: () => {
            const value = !appAccessibilite.dyslexieLexend;
            setDyslexieLexend(value);
            setPreference("dyslexie-lexend", value ? "true" : "false");
            if (value) {
              setPreference("dyslexie-opendys", "false");
              setPreference("dyslexie-arial", "false");
            }
          },
        },
        {
          key: "accessibilite-dyslexie",
          label: labels?.MENU_A11Y_POLICE_ARIAL ?? "Police : Arial",
          icon: (
            <CheckOutlined
              aria-label={
                appAccessibilite.dyslexieArial
                  ? "Fonctionnalité activée"
                  : "Fonctionnalité désactivée"
              }
              className={appAccessibilite.dyslexieArial ? "mr-1" : "mr-1 v-hidden"}
            />
          ),
          onClick: () => {
            const value = !appAccessibilite.dyslexieArial;
            setDyslexieArial(value);
            setPreference("dyslexie-arial", value ? "true" : "false");
            if (value) {
              setPreference("dyslexie-opendys", "false");
              setPreference("dyslexie-lexend", "false");
            }
          },
        },
        {
          key: "accessibilite-dyslexie-opendys",
          label: labels?.MENU_A11Y_POLICE_OPENDYS ?? "Police : OpenDys",
          icon: (
            <CheckOutlined
              aria-label={
                appAccessibilite.dyslexieOpenDys
                  ? "Fonctionnalité activée"
                  : "Fonctionnalité désactivée"
              }
              className={appAccessibilite.dyslexieOpenDys ? "mr-1" : "mr-1 v-hidden"}
            />
          ),
          onClick: () => {
            const value = !appAccessibilite.dyslexieOpenDys;
            setDyslexieOpenDys(value);
            setPreference("dyslexie-opendys", value ? "true" : "false");
            if (value) {
              setPreference("dyslexie-arial", "false");
              setPreference("dyslexie-lexend", "false");
            }
          },
        },
        {
          key: "divider-accessibilite-2",
          type: "divider",
        },
        {
          key: "accessibilite-police-large",
          label: labels?.MENU_A11Y_POLICE_LARGE ?? "Police large",
          icon: (
            <CheckOutlined
              aria-label={
                appAccessibilite.policeLarge
                  ? "Fonctionnalité activée"
                  : "Fonctionnalité désactivée"
              }
              className={appAccessibilite.policeLarge ? "mr-1" : "mr-1 v-hidden"}
            />
          ),
          onClick: () => {
            const value = !appAccessibilite.policeLarge;
            setPoliceLarge(value);
            setPreference("police-large", value ? "true" : "false");
          },
        },
      ],
    },
  ];
}
