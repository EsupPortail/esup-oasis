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
import { CategorieTagEdition } from "@controls/Admin/Referentiel/Tags/CategorieTagEdition";
import { ICategorieTag, ITag } from "@api";
import { CategoriesTagTable } from "@controls/Table/Admin/CategoriesTagTable";

/**
 * Renders admin page for managing Amenagements.
 *
 * @returns {ReactElement} The rendered Amenagements component.
 */
export default function CategoriesTags(): ReactElement {
  const [editedItem, setEditedItem] = useState<ICategorieTag | undefined>();

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
            key: "tags",
            title: "Tags",
          },
        ]}
      />{" "}
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Catégories de tags
      </Typography.Title>
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <CategoriesTagTable editedItem={editedItem} setEditedItem={setEditedItem} />
        </Col>
      </Row>
      {editedItem && <CategorieTagEdition editedItem={editedItem} setEditedItem={setEditedItem} />}
      <FloatButton
        icon={<PlusOutlined />}
        type="primary"
        tooltip="Ajouter une catégorie de tag"
        onClick={() => {
          setEditedItem({
            actif: true,
          } as ITag);
        }}
      />
    </Layout.Content>
  );
}
