/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { CSSProperties, ReactElement } from "react";
import { EditOutlined, EyeOutlined } from "@ant-design/icons";
import { Card, Skeleton, Space, Tabs, TabsProps } from "antd";
import { useAuth } from "@/auth/AuthProvider";
import { useLabelsMenu } from "@controls/AppLayout/useLabelsMenu";
import { MENUS_ADMIN_CONFIG } from "@controls/Admin/Menus/menusAdminConfig";
import MenuLabelTree from "@controls/Admin/Menus/MenuLabelTree";
import MenuPreview from "@controls/Admin/Menus/preview/MenuPreview";
import "@controls/Admin/Menus/Menus.scss";

/** Page d'administration des libellés MENU_* : un onglet par rôle applicatif, édition + aperçu live. */
export default function MenusAdminTabs(): ReactElement {
  const auth = useAuth();
  const { labels, parametresParCle, isFetching, setSurcharge } = useLabelsMenu();
  const afficherCle = auth.user?.isAdminTechnique ?? false;

  if (isFetching) return <Skeleton active />;

  const items: TabsProps["items"] = MENUS_ADMIN_CONFIG.map((onglet) => ({
    key: onglet.key,
    label: (
      <span className="menus-admin-tab-label">
        <span className="menus-admin-tab-dot" style={{ background: onglet.accent }} aria-hidden />
        {onglet.label}
      </span>
    ),
    children: (
      <div
        className="menus-admin-workbench"
        style={{ "--onglet-accent": onglet.accent } as CSSProperties}
      >
        <Card
          className="menus-admin-panel"
          title={
            <Space>
              <EditOutlined aria-hidden />
              {`Libellés pour le rôle ${onglet.label}`}
            </Space>
          }
        >
          <MenuLabelTree
            items={onglet.items}
            parametresParCle={parametresParCle}
            onChange={setSurcharge}
            afficherCle={afficherCle}
          />
        </Card>
        <Card
          className="menus-admin-panel"
          title={
            <Space>
              <EyeOutlined aria-hidden />
              {`Aperçu du menu complet — Rôle ${onglet.label}`}
            </Space>
          }
        >
          <MenuPreview onglet={onglet} labels={labels} />
        </Card>
      </div>
    ),
  }));

  return <Tabs type="card" items={items} />;
}
