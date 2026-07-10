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
import "@routes/administration/Administration.scss";
import { HomeFilled, PlusOutlined } from "@ant-design/icons";
import { NavLink, useParams } from "react-router-dom";
import ADMIN_CONFIG from "@routes/administration/AdminConfig";
import { IReferentielEditable } from "@lib";
import { ReferentielItemEdition } from "@controls/Admin/Referentiel/ReferentielItemEdition";
import { ReferentielTable } from "@controls/Table/Admin/ReferentielTable";
import { removeAccents } from "@utils/string";

/**
 * Renders a page for managing a referentiel.
 * The edited referentiel is passed as a parameter in the URL (referentielId).
 *
 * @return {ReactElement} The rendered referentiel component.
 */
export default function Referentiel(): ReactElement {
  const { referentielId } = useParams<"referentielId">();
  const referentielConfig = ADMIN_CONFIG.find(
    (r) => r.id === referentielId,
  ) as (typeof ADMIN_CONFIG)[number];
  const [editedItem, setEditedItem] = useState<IReferentielEditable | undefined>();

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
            key: "referentiels",
            title: (
              <NavLink to={`/administration#${removeAccents(referentielConfig.categorie)}`}>
                <Space>
                  {referentielConfig.categorie.charAt(0).toUpperCase() +
                    referentielConfig.categorie.slice(1)}
                </Space>
              </NavLink>
            ),
          },
          {
            key: "referentiel",
            title: referentielConfig.title,
          },
        ]}
      />
      <Typography.Title level={1}>Référentiels</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        {referentielConfig.title}
      </Typography.Title>
      {editedItem && (
        <ReferentielItemEdition
          referentielConfig={referentielConfig}
          editedItem={editedItem}
          setEditedItem={setEditedItem}
        />
      )}
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <ReferentielTable
            referentielConfig={referentielConfig}
            editedItem={editedItem}
            onEdit={setEditedItem}
          />
        </Col>
      </Row>
      <FloatButton
        icon={<PlusOutlined />}
        type="primary"
        tooltip="Ajouter"
        onClick={() =>
          setEditedItem({
            libelle: "",
            actif: true,
          })
        }
      />
    </Layout.Content>
  );
}
