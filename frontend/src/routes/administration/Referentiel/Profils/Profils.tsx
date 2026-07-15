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
import { ProfilsEdition } from "@controls/Admin/Referentiel/Profils/ProfilsEdition";
import { ProfilsTable } from "@controls/Table/Admin/ProfilsTable";
import { IProfil } from "@api";

/**
 * Renders admin page for managing Profils.
 *
 * @returns {ReactElement} The rendered Profils component.
 */
export default function Profils(): ReactElement {
  const [editedItem, setEditedItem] = useState<IProfil | undefined>();

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
            key: "benefificaires",
            title: (
              <NavLink to="/administration#beneficiaires">
                <Space>Bénéficiaires</Space>
              </NavLink>
            ),
          },
          {
            key: "profils",
            title: "Profils",
          },
        ]}
      />{" "}
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Profils
      </Typography.Title>
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <ProfilsTable
            onEdit={(item) => {
              setEditedItem(undefined);
              setEditedItem(item);
            }}
            editedItem={editedItem}
          />
        </Col>
      </Row>
      {editedItem && <ProfilsEdition editedItem={editedItem} setEditedItem={setEditedItem} />}
      <FloatButton
        icon={<PlusOutlined />}
        type="primary"
        tooltip="Ajouter une catégorie d'évènement"
        onClick={() => {
          setEditedItem({
            actif: true,
            avecTypologie: false,
          } as IProfil);
        }}
      />
    </Layout.Content>
  );
}
