/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Utilisateur } from "@lib";
import { NavigateFunction } from "react-router-dom";
import { Button, MenuProps } from "antd";
import React from "react";

/**
 * Generates the menu items for the planning page (role Planificateur)
 *
 * @param setSelectedKey
 * @param {Object} user - The user object.
 * @param {Function} navigate - The navigation function.
 * @param labels
 * @return {Array} The generated menu items.
 */
export const menuItemPlanningPlanificateur = (
  setSelectedKey: (key: string) => void,
  user: Utilisateur,
  navigate: NavigateFunction,
  labels?: Record<string, string>,
): MenuProps["items"] => [
  {
    key: "planning",
    label: (
      <Button
        type="text"
        className="no-hover p-0"
        onClick={() => {
          setSelectedKey("planning");
          navigate("/planning");
        }}
      >
        {labels?.MENU_PLANNING_PLANIF ?? "Planning"}
      </Button>
    ),
    children: [
      {
        key: "planning-item",
        label: labels?.MENU_PLANNING_INTERVENTIONS ?? "Planning des interventions",
        onClick: () => {
          setSelectedKey("planning");
          navigate("/planning");
        },
      },
      {
        key: "interventions-forfait",
        label:
          labels?.MENU_PLANNING_INTERVENTIONS_FORFAIT ??
          "Interventions au forfait (prise de notes)",
        onClick: () => {
          setSelectedKey("planning");
          navigate("/interventions/forfait");
        },
      },
      user?.isGestionnaire
        ? {
            key: "interventions-renforts",
            label:
              labels?.MENU_PLANNING_VALIDATION_RENFORTS ??
              "Validation des interventions des renforts",
            onClick: () => {
              setSelectedKey("planning");
              navigate("/interventions/renforts");
            },
          }
        : null,
      user?.isRenfort
        ? {
            key: "mes-interventions",
            label: labels?.MENU_PLANNING_MES_INTERVENTIONS ?? "Vos interventions (renfort)",
            onClick: () => {
              setSelectedKey("planning");
              navigate("/mes-interventions");
            },
          }
        : null,
    ],
  },
];
