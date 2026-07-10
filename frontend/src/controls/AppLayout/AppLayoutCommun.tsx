/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { UserSwitchOutlined } from "@ant-design/icons";
import { ROLES, RoleValues } from "@lib";
import React, { ReactElement } from "react";
import { AuthContextType } from "@/auth/AuthProvider";
import { UtilisateurAvatar } from "@controls/Avatars/UtilisateurAvatar";
import { Flex, Space } from "antd";
import { IUtilisateur } from "@api";
import { ItemType } from "antd/es/menu/interface";

/**
 * A function that returns a menu profile object based on the user authentication context.
 * @param {AuthContextType} auth - The authentication context.
 * @param labels
 * @returns {ItemType[]} The menu profile object, or null if impersonation is not enabled.
 */
export const menuProfils = (auth: AuthContextType, labels?: Record<string, string>): ItemType[] => {
  return auth.impersonate
    ? [
        {
          key: "impersonate",
          icon: <UserSwitchOutlined />,
          className: "text-warning",
          label: labels?.MENU_UTILISATEUR_RECUP_IDENTITE ?? "Récupérer mon identité",
          onClick: () => {
            auth.removeImpersonate();
          },
        },
      ]
    : [];
};

interface IAvatarUtilisateurMenu {
  user: IUtilisateur | undefined;
  isFetching: number;
  isImpersonate?: boolean;
}

/**
 * Render the Avatar for the user in the menu.
 *
 * @param {IAvatarUtilisateurMenu} props - The props for the AvatarUtilisateurMenu component.
 * @param {IUtilisateur} props.user - The user data.
 * @param {number} props.isFetching - The number of ongoing fetch operations.
 * @param {boolean} props.isImpersonate - Flag indicating if the user is being impersonated.
 * @returns {ReactElement} - The rendered AvatarUtilisateurMenu component.
 */
export const AvatarUtilisateurMenu = ({
  user,
  isFetching,
  isImpersonate,
}: IAvatarUtilisateurMenu): ReactElement => {
  return (
    <>
      <UtilisateurAvatar
        style={{ marginTop: 1 }}
        utilisateur={user}
        role={RoleValues.ROLE_GESTIONNAIRE}
        className={isImpersonate ? "bg-warning" : ""}
        loading={isFetching > 0}
        shape="circle"
        showTooltip={false}
      />
    </>
  );
};

/**
 * Render the user information in the menu.
 *
 * @param {object} props - The properties for rendering the user information.
 * @param {boolean} props.isImpersonate - Flag indicating if the user is impersonating.
 *
 * @returns {ReactElement} - The rendered user information.
 */
export const LabelUtilisateurMenu = ({
  auth,
  apiFetching,
  isImpersonate,
}: {
  auth: AuthContextType | undefined;
  isImpersonate: boolean;
  apiFetching: number;
}): ReactElement => {
  if (!auth) return <></>;

  return (
    <Flex>
      <AvatarUtilisateurMenu
        user={auth.user as IUtilisateur}
        isFetching={apiFetching}
        isImpersonate={auth.impersonate !== undefined}
      />
      <Space
        className={`user-space${isImpersonate ? " text-warning" : ""}`}
        size={2}
        orientation="vertical"
      >
        <span className="identite">{`${auth.user?.prenom} ${auth.user?.nom?.toLocaleUpperCase()}`}</span>
        <span className="role">{ROLES.find((r) => r.value === auth.user?.roleCalcule)?.label}</span>
      </Space>
    </Flex>
  );
};
