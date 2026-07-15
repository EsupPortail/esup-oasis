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
import { TypesEvenementsEdition } from "@controls/Admin/Referentiel/TypesEvenements/TypesEvenementsEdition";
import { TypesEvenementsTable } from "@controls/Table/Admin/TypesEvenementsTable";
import { ITypeEvenement } from "@api";

/**
 * Renders the admin page for managing TypesEvenements.
 * @returns {ReactElement} The TypesEvenements component.
 */
export default function TypesEvenements(): ReactElement {
  const [editedItem, setEditedItem] = useState<ITypeEvenement | undefined>();

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
            key: "planification",
            title: (
              <NavLink to="/administration#planification">
                <Space>Planification</Space>
              </NavLink>
            ),
          },
          {
            key: "types-evenements",
            title: "Catégories d'évènements",
          },
        ]}
      />{" "}
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Catégories d'évènements
      </Typography.Title>
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <TypesEvenementsTable onEdit={setEditedItem} editedItem={editedItem} />
        </Col>
      </Row>
      {editedItem && (
        <TypesEvenementsEdition typeEvenement={editedItem} setTypeEvenement={setEditedItem} />
      )}
      <FloatButton
        icon={<PlusOutlined />}
        type="primary"
        tooltip="Ajouter une catégorie d'évènement"
        onClick={() => {
          setEditedItem({
            actif: true,
            visibleParDefaut: true,
          } as ITypeEvenement);
        }}
      />
    </Layout.Content>
  );
}
