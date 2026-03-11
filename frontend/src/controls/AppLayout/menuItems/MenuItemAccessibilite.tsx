/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IAccessibilite } from "../../../redux/context/IAccessibilite";
import { Dispatch } from "redux";
import { Button, MenuProps } from "antd";
import Icon, { CheckOutlined } from "@ant-design/icons";
import {
   setAccessibiliteContrast,
   setAccessibiliteDyslexieArial,
   setAccessibiliteDyslexieLexend,
   setAccessibiliteDyslexieOpenDys,
   setPoliceLarge,
} from "../../../redux/actions/Accessibilite";
import React from "react";
import { ReactComponent as IconeAccessibilite } from "../../../assets/images/accessibilite.svg";

/**
 * Generate menu items for accessibility options.
 *
 * @param {IAccessibilite} appAccessibilite - The accessibility settings object.
 * @param {Dispatch} dispatch - The Redux dispatch function.
 * @param setPreference
 * @returns {MenuProps["items"]} - The generated menu items.
 */
export function menuItemAccessibilite(
   appAccessibilite: IAccessibilite,
   dispatch: Dispatch,
   setPreference: (key: string, value: string) => void,
): MenuProps["items"] {
   return [
      {
         key: "accessibilite",
         title: "Options d'accessibilité",
         className: "menu-small-item no-indicator item-accessibilite",
         label: (
            <Button
               type="text"
               className="bg-transparent p-0 pt-1"
               aria-label="Ajuster les préférences d'accessibilité"
            >
               <span className="show-on-overflow">Accessibilité</span>
               <Icon component={IconeAccessibilite} aria-hidden className="hide-on-overflow" />
            </Button>
         ),
         children: [
            {
               key: "accessibilite-contraste",
               label: "Contraste",
               icon: (
                  <CheckOutlined
                     aria-label={
                        appAccessibilite.contrast
                           ? "Fonctionnalité activée"
                           : "Fonctionnalité désactivée"
                     }
                     className={appAccessibilite.contrast ? "mr-1" : "mr-1 v-hidden"}
                  />
               ),
               onClick: () => {
                  dispatch(setAccessibiliteContrast(!appAccessibilite.contrast));
                  setPreference("contrast", !appAccessibilite.contrast ? "true" : "false");
               },
            },
            {
               key: "divider-accessibilite",
               type: "divider",
            },
            {
               key: "accessibilite-dyslexie-lexend",
               label: "Police : Lexend",
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
                  dispatch(setAccessibiliteDyslexieLexend(value));
                  setPreference("dyslexie-lexend", value ? "true" : "false");
                  if (value) {
                     setPreference("dyslexie-opendys", "false");
                     setPreference("dyslexie-arial", "false");
                  }
               },
            },
            {
               key: "accessibilite-dyslexie",
               label: "Police : Arial",
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
                  dispatch(setAccessibiliteDyslexieArial(value));
                  setPreference("dyslexie-arial", value ? "true" : "false");
                  if (value) {
                     setPreference("dyslexie-opendys", "false");
                     setPreference("dyslexie-lexend", "false");
                  }
               },
            },
            {
               key: "accessibilite-dyslexie-opendys",
               label: "Police : OpenDys",
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
                  dispatch(setAccessibiliteDyslexieOpenDys(value));
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
               label: "Police large",
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
                  dispatch(setPoliceLarge(value));
                  setPreference("police-large", value ? "true" : "false");
               },
            },
         ],
      },
   ];
}
