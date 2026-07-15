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

export const menuItemDemandesForMembresCommission = (
  setSelectedKey: (key: string) => void,
  navigate: NavigateFunction,
  labels?: Record<string, string>,
): MenuProps["items"] => {
  if (env.REACT_APP_GERER_DEMANDES) {
    return [
      {
        key: "demandeurs-commission",
        className: "mr-auto",
        children: [],
        popupClassName: "d-none",
        label: (
          <Button
            type="text"
            className="no-hover p-0"
            onClick={() => {
              setSelectedKey("demandeurs-commission");
              navigate("/demandes");
            }}
          >
            {labels?.MENU_DEMANDES_COMMISSION ?? "Demandes"}
          </Button>
        ),
      },
    ];
  }

  return [];
};
