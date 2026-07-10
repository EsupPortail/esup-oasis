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
import { env } from "@/env";

/**
 * Generates the menu items for the beneficiary/intervenant planning menu.
 *
 * @param setSelectedKey
 * @param navigate - The function to navigate to a specific page.
 * @param className - Optional class name for the menu item.
 * @param labels
 * @returns An array of menu items for the beneficiary/intervenant planning menu.
 */
export function menuItemDemandeur(
  setSelectedKey: (key: string) => void,
  navigate: NavigateFunction,
  className?: string,
  labels?: Record<string, string>,
): MenuProps["items"] {
  if (env.REACT_APP_GERER_DEMANDES)
    return [
      {
        key: "demandes",
        label: (
          <Button
            type="text"
            className="no-hover p-0"
            onClick={() => {
              setSelectedKey("demandes");
              navigate("/demandes");
            }}
          >
            {labels?.MENU_DEMANDES_DEMANDEUR ?? "Demandes"}
          </Button>
        ),
        className: className,
        children: [],
        popupClassName: "d-none",
      },
    ];

  return [];
}
