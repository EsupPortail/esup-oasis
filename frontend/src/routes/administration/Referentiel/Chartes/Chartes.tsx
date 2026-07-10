/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useState } from "react";
import { Breadcrumb, Col, FloatButton, Layout, Row, Space, Typography } from "antd";
import { NavLink } from "react-router-dom";
import { HomeFilled, PlusOutlined } from "@ant-design/icons";
import { ICharte } from "@api";
import { ChartesEdition } from "@controls/Admin/Referentiel/Chartes/ChartesEdition";
import { ChartesTable } from "@controls/Table/Admin/ChartesTable";

/**
 * Renders admin page for managing ClubsSportifs.
 *
 * @returns {ReactElement} The rendered ClubsSportifs component.
 */
export default function Chartes(): ReactElement {
  const [editedItem, setEditedItem] = useState<ICharte | undefined>();

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
            key: "demandes",
            title: (
              <NavLink to="/administration#demandes">
                <Space>Demandeurs</Space>
              </NavLink>
            ),
          },
          {
            key: "chartes",
            title: "Chartes",
          },
        ]}
      />{" "}
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Chartes
      </Typography.Title>
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <ChartesTable onEdit={setEditedItem} editedItem={editedItem} />
        </Col>
      </Row>
      {editedItem && <ChartesEdition editedItem={editedItem} setEditedItem={setEditedItem} />}
      <FloatButton
        icon={<PlusOutlined />}
        type="primary"
        tooltip="Ajouter une charte"
        onClick={() => {
          setEditedItem({} as ICharte);
        }}
      />
    </Layout.Content>
  );
}
