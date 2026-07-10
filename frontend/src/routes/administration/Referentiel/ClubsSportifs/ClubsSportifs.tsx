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
import { ClubSportifEdition } from "@controls/Admin/Referentiel/ClubsSportifs/ClubSportifEdition";
import { IClubSportif } from "@api";
import { ClubsSportifsTable } from "@controls/Table/Admin/ClubsSportifsTable";

/**
 * Renders admin page for managing ClubsSportifs.
 *
 * @returns {ReactElement} The rendered ClubsSportifs component.
 */
export default function ClubsSportifs(): ReactElement {
  const [editedItem, setEditedItem] = useState<IClubSportif | undefined>();

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
            key: "clubsSportifs",
            title: "Clubs sportifs",
          },
        ]}
      />{" "}
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Clubs sportifs
      </Typography.Title>
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <ClubsSportifsTable
            onEdit={(item) => {
              setEditedItem(undefined);
              setEditedItem(item);
            }}
            editedItem={editedItem}
          />
        </Col>
      </Row>
      {editedItem && <ClubSportifEdition editedItem={editedItem} setEditedItem={setEditedItem} />}
      <FloatButton
        icon={<PlusOutlined />}
        type="primary"
        tooltip="Ajouter un club sportif"
        onClick={() => {
          setEditedItem({
            actif: true,
          } as IClubSportif);
        }}
      />
    </Layout.Content>
  );
}
