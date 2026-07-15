/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { NavigateFunction } from "react-router-dom";
import { Utilisateur } from "@lib";
import { Button, MenuProps } from "antd";
import React from "react";

/**
 * Creates the menu items for the "Bénéficiaires" menu item.
 *
 * @param setSelectedKey
 * @param {Function} navigate - The navigate function to be called when a menu item is clicked.
 * @param user
 * @param labels
 * @return {Array} - An array of menu item objects.
 */
export const menuItemBeneficiaires = (
  setSelectedKey: (key: string) => void,
  navigate: NavigateFunction,
  user: Utilisateur,
  labels?: Record<string, string>,
): MenuProps["items"] => [
  {
    key: "beneficiaires",
    label: (
      <Button
        type="text"
        className="no-hover p-0"
        onClick={() => {
          setSelectedKey("beneficiaires");
          navigate(user.isGestionnaire ? "/beneficiaires" : "/amenagements?mode=amenagement");
        }}
      >
        {labels?.MENU_BENEFICIAIRES ?? "Bénéficiaires"}
      </Button>
    ),
    children: user.isGestionnaire
      ? [
          {
            key: "beneficiaires-item",
            label: labels?.MENU_BENEFICIAIRES_LISTE ?? "Bénéficiaires",
            onClick: () => {
              setSelectedKey("beneficiaires");
              navigate("/beneficiaires");
            },
          },
          {
            key: "amenagements-beneficiaires",
            label: labels?.MENU_BENEFICIAIRES_AMENAGEMENT_PAR_BENEF ?? "Aménagements par bénéf.",
            onClick: () => {
              setSelectedKey("beneficiaires");
              navigate("/amenagements?mode=beneficiaire");
            },
          },
          {
            key: "amenagements",
            label: labels?.MENU_BENEFICIAIRES_AMENAGEMENT ?? "Aménagements",
            onClick: () => {
              setSelectedKey("beneficiaires");
              navigate("/amenagements?mode=amenagement");
            },
          },
        ]
      : [],
  },
];
