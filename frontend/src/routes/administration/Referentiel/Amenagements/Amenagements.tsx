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
import { TypeAmenagementEdition } from "@controls/Admin/Referentiel/Amenagements/TypeAmenagementEdition";
import { ICategorieAmenagement, ITypeAmenagement } from "@api";
import { CategoriesAmenagementsTable } from "@controls/Table/Admin/CategoriesAmenagementsTable";
import { CategorieAmenagementEdition } from "@controls/Admin/Referentiel/Amenagements/CategorieAmenagementEdition";

/**
 * Renders admin page for managing Amenagements.
 *
 * @returns {ReactElement} The rendered Amenagements component.
 */
export function Amenagements(): ReactElement {
  const [editedCategorie, setEditedCategorie] = useState<ICategorieAmenagement | undefined>();
  const [editedType, setEditedType] = useState<ITypeAmenagement | undefined>();

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
            key: "amenagements",
            title: "Aménagements",
          },
        ]}
      />
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Aménagements
      </Typography.Title>
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <CategoriesAmenagementsTable
            onEditCategorie={(item) => {
              setEditedCategorie(undefined);
              setEditedCategorie(item);
            }}
            onEditType={(item) => {
              setEditedType(undefined);
              setEditedType(item);
            }}
            editedItem={editedCategorie}
          />
        </Col>
      </Row>
      {editedCategorie && (
        <CategorieAmenagementEdition
          editedItem={editedCategorie}
          setEditedItem={setEditedCategorie}
        />
      )}
      {editedType && (
        <TypeAmenagementEdition editedItem={editedType} setEditedItem={setEditedType} />
      )}
      <FloatButton
        icon={<PlusOutlined />}
        type="primary"
        tooltip="Ajouter une catégorie d'aménagement"
        onClick={() => {
          setEditedCategorie({
            actif: true,
          } as ITypeAmenagement);
        }}
      />
    </Layout.Content>
  );
}

export default Amenagements;
