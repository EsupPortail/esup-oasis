/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useState } from "react";
import { Breadcrumb, Col, Layout, Row, Space, Typography } from "antd";
import { HomeFilled } from "@ant-design/icons";
import { NavLink } from "react-router-dom";
import { IComposante } from "@api";
import ReferentsTable from "@controls/Table/Admin/ReferentsTable";
import { ComposanteEdition } from "@controls/Admin/Referentiel/Referents/ComposanteEdition";

/**
 * Renders the user administration page.
 *
 * @returns {ReactElement} The rendered user administration page.
 */
export default function Referents(): ReactElement {
  const [editedItem, setEditedItem] = useState<IComposante>();

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
            key: "users",
            title: (
              <NavLink to="/administration#referents">
                <Space>Utilisateurs</Space>
              </NavLink>
            ),
          },
          {
            key: "referents",
            title: "Référents de composante",
          },
        ]}
      />{" "}
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Référents de composante
      </Typography.Title>
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <ReferentsTable onEdit={(u) => setEditedItem(u)} />
        </Col>
        {editedItem && <ComposanteEdition editedItem={editedItem} setEditedItem={setEditedItem} />}
      </Row>
    </Layout.Content>
  );
}
