/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { NavigateFunction } from "react-router-dom";
import { Button, MenuProps } from "antd";
import React from "react";

/**
 * Creates the menu items for the "Demandeurs" menu item.
 *
 * @param setSelectedKey
 * @param {NavigateFunction} navigate - The function used for navigating to a specific route.
 * @return {MenuProps["items"]} - The menu items for the "Intervenants" menu item.
 */
export const menuItemDemandeurs = (
   setSelectedKey: (key: string) => void,
   navigate: NavigateFunction,
): MenuProps["items"] => [
   {
      key: "demandeurs",
      label: (
         <Button
            type="text"
            className="no-hover p-0"
            onClick={() => {
               navigate("/demandeurs");
               setSelectedKey("demandeurs");
            }}
         >
            Demandeurs
         </Button>
      ),
      children: [],
      popupClassName: "d-none",
   },
];
