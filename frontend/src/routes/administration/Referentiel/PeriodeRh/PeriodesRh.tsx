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
import { PeriodesRhEdition } from "@controls/Admin/Referentiel/PeriodeRh/PeriodesRhEdition";
import { PeriodesRhTable } from "@controls/Table/Admin/PeriodesRhTable";
import { IPeriode } from "@api";

/**
 * Renders the Administration page for managing RH periods.
 *
 * @returns {ReactElement} The rendered Administration page.
 */
export default function PeriodesRh(): ReactElement {
  const [editedItem, setEditedItem] = useState<IPeriode | undefined>();
  const [openDrawer, setOpenDrawer] = useState<boolean>(false);

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
            key: "periodes-rh",
            title: "Périodes RH",
          },
        ]}
      />{" "}
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Périodes RH
      </Typography.Title>
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <PeriodesRhTable
            onEdit={(p: IPeriode) => {
              setEditedItem(p);
              setOpenDrawer(true);
            }}
          />
        </Col>
      </Row>
      <FloatButton
        icon={<PlusOutlined />}
        type="primary"
        tooltip="Ajouter une période"
        onClick={() => {
          setEditedItem(undefined);
          setOpenDrawer(true);
        }}
      />
      {openDrawer && (
        <PeriodesRhEdition
          periode={editedItem}
          setPeriode={setEditedItem}
          onClose={() => setOpenDrawer(false)}
        />
      )}
    </Layout.Content>
  );
}
