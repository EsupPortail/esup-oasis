/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { AuthContextType } from "@/auth/AuthProvider";
import { NavigateFunction } from "react-router-dom";
import { ItemType, MenuItemType } from "antd/es/menu/interface";
import { LabelUtilisateurMenu, menuProfils } from "@controls/AppLayout/AppLayoutCommun";
import { LogoutOutlined, PieChartOutlined, SettingOutlined, UserOutlined } from "@ant-design/icons";
import { queryClient } from "@/queryClient";
import React from "react";
import { Button } from "antd";

/**
 * Returns the menu items for the user menu.
 *
 * @param setSelectedKey
 * @param {AuthContextType} auth - The authentication context.
 * @param {number} apiFetching - The API fetching status.
 * @param {NavigateFunction} navigate - The navigation function.
 * @param labels
 * @returns {Object["items"]} - The menu items.
 */
export const menuItemUtilisateur = (
  setSelectedKey: (key: string) => void,
  auth: AuthContextType,
  apiFetching: number,
  navigate: NavigateFunction,
  labels?: Record<string, string>,
): ItemType<MenuItemType>[] => {
  if (!auth) return [];

  return [
    {
      key: "user",
      label: (
        <>
          <Button type="text" className="bg-transparent hide-on-overflow">
            <LabelUtilisateurMenu
              auth={auth}
              apiFetching={apiFetching}
              isImpersonate={auth.impersonate !== undefined}
            />
          </Button>
          <Button type="text" className="bg-transparent show-on-overflow p-0">
            <LabelUtilisateurMenu
              auth={auth}
              apiFetching={apiFetching}
              isImpersonate={auth.impersonate !== undefined}
            />
          </Button>
        </>
      ),
      className: `user no-indicator`,
      style: { fontWeight: 300 },
      children: [
        auth.user?.isBeneficiaire || auth.user?.isIntervenant
          ? {
              key: "mon-profil",
              icon: <UserOutlined />,
              label: labels?.MENU_UTILISATEUR_MON_PROFIL ?? "Mon profil",
              onClick: () => {
                setSelectedKey("user");
                navigate("/profil");
              },
            }
          : null,
        {
          key: "user-divider-1",
          type: "divider",
        },

        auth.user?.isAdmin
          ? {
              key: "admin",
              icon: <SettingOutlined />,
              label: labels?.MENU_UTILISATEUR_ADMINISTRATION ?? "Administration",
              onClick: () => {
                setSelectedKey("user");
                navigate("/administration");
              },
            }
          : null,
        auth.user?.isGestionnaire
          ? {
              key: "bilans",
              icon: <PieChartOutlined />,
              label: labels?.MENU_UTILISATEUR_BILANS ?? "Bilans",
              onClick: () => {
                setSelectedKey("user");
                navigate("/bilans");
              },
            }
          : null,

        {
          key: "user-divider",
          type: "divider",
        },
        ...menuProfils(auth, labels),
        {
          key: "exit",
          icon: <LogoutOutlined />,
          label: labels?.MENU_UTILISATEUR_DECONNEXION ?? "Déconnexion",
          onClick: () => {
            queryClient.clear();
            auth.signOut(() => window.location.assign(window.location.origin.toString()));
          },
        },
      ],
    },
  ];
};
