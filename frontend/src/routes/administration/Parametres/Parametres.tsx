/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Breadcrumb, Layout, Space, Typography } from "antd";
import { NavLink } from "react-router";
import { HomeFilled } from "@ant-design/icons";
import ParametresTable from "@controls/Table/Admin/ParametresTable";
import "@routes/administration/Parametres/Parametres.scss";

/**
 * Renders the administration page for managing application parameters.
 *
 * @returns {ReactElement} The content to be rendered.
 */
export default function Parametres(): ReactElement {
  return (
    <Layout.Content className="administration" style={{ padding: "0 50px" }}>
      <Breadcrumb
        className="mt-2"
        items={[
          {
            key: "administration",
            title: (
              <NavLink to="/administration">
                <Space>
                  <HomeFilled />
                  Administration
                </Space>
              </NavLink>
            ),
          },
          {
            key: "parametres",
            title: "Paramètres de l'application",
          },
        ]}
      />
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Paramètres
      </Typography.Title>
      <Typography.Paragraph>
        Vous pouvez modifier ici les constantes utilisées par l'application.
      </Typography.Paragraph>
      <ParametresTable masquerParametresMenu />
    </Layout.Content>
  );
}
