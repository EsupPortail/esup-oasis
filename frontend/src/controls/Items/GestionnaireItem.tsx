/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { memo, ReactElement } from "react";
import { RoleValues } from "@lib";
import { Breakpoint, Button, Skeleton, Space } from "antd";
import { UtilisateurAvatar } from "@controls/Avatars/UtilisateurAvatar";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { useApi } from "@context/api/ApiProvider";
import { MinusOutlined } from "@ant-design/icons";
import { IUtilisateur } from "@api";
import { mailtoHref } from "@utils/url";

interface IItemGestionnaire {
  gestionnaire?: IUtilisateur;
  gestionnaireId?: string;
  showAvatar?: boolean;
  responsive?: Breakpoint;
  initialePrenom?: boolean;
  showEmail?: boolean;
  className?: string;
}

/**
 * Renders a component displaying information about a gestionnaire item.
 *
 * @param {IItemGestionnaire} config - The configuration object.
 * @param {IUtilisateur} [config.gestionnaire] - The gestionnaire object to display.
 * @param {string} [config.gestionnaireId] - The id of the gestionnaire item.
 * @param {boolean} [config.showAvatar=true] - Determines if the avatar should be displayed.
 * @param {Breakpoint} [config.responsive] - The breakpoint at which the avatar should be displayed.
 * @param {boolean} [config.initialePrenom=false] - Determines if the initial of the prenom should be displayed.
 * @param {boolean} [config.showEmail=false] - Determines if the email should be displayed.
 *
 * @return {ReactElement} - The JSX element representing the gestionnaire item.
 */
function GestionnaireItem({
  gestionnaire,
  gestionnaireId,
  showAvatar = true,
  responsive,
  initialePrenom = false,
  showEmail = false,
  className,
}: IItemGestionnaire): ReactElement {
  const { data, isFetching } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: gestionnaireId as string,
    enabled: !!gestionnaireId,
  });
  const item = gestionnaire ?? data;
  const screens = useBreakpoint();

  if (!gestionnaire && !gestionnaireId) return <MinusOutlined />;

  if (isFetching) {
    return (
      <Space className={className}>
        {showAvatar && (!responsive || screens[responsive]) && (
          <UtilisateurAvatar role={RoleValues.ROLE_GESTIONNAIRE} loading />
        )}
        <div style={{ lineHeight: 1 }}>
          <Skeleton.Input active />
        </div>
      </Space>
    );
  }

  if (!item) return <></>;

  return (
    <Space className={className}>
      {showAvatar && (!responsive || screens[responsive]) && (
        <UtilisateurAvatar utilisateur={item} role={RoleValues.ROLE_GESTIONNAIRE} />
      )}
      <div style={{ lineHeight: 1 }}>
        <span style={{ whiteSpace: "nowrap" }}>
          {initialePrenom ? `${(item?.prenom || "")[0].toLocaleUpperCase()}.` : item?.prenom}{" "}
          {item?.nom?.toLocaleUpperCase()}
        </span>
        {showEmail && item?.email && (
          <Button
            size="small"
            type="text"
            className="legende fs-08 m-0 p-0 d-block contrast-no-border"
            onClick={() => {
              window.location.href = mailtoHref(item?.email ?? "");
            }}
            aria-label={`Envoyer un email au gestionnaire (${item?.prenom} ${item?.nom})`}
          >
            {item?.email}
          </Button>
        )}
      </div>
    </Space>
  );
}

const GestionnaireItemMemo = memo(
  GestionnaireItem,
  (prev, next) =>
    prev.gestionnaireId === next.gestionnaireId &&
    prev.gestionnaire?.["@id"] === next.gestionnaire?.["@id"] &&
    prev.showAvatar === next.showAvatar &&
    prev.showEmail === next.showEmail,
);
export { GestionnaireItemMemo as GestionnaireItem };
