/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useState } from "react";
import { Breadcrumb, Card, Layout, Space, Typography } from "antd";
import { NavLink } from "react-router-dom";
import { HomeFilled, InfoCircleFilled } from "@ant-design/icons";
import { ServicesFaitsItem } from "@controls/Admin/Bilans/ServicesFaitsItem";
import { IPeriode } from "@api";
import PeriodeField from "@controls/Forms/PeriodeField";

/**
 * Renders the ServicesFaits component for the administration page.
 *
 * This component displays a form for selecting a period, and a list of services faits for the selected period.
 *
 * @returns {ReactElement} The ServicesFaits component
 */
export default function ServicesFaits(): ReactElement {
  const [periode, setPeriode] = useState<IPeriode>();

  return (
    <Layout.Content className="administration" style={{ padding: "0 50px" }}>
      <Breadcrumb
        className="mt-2"
        items={[
          {
            key: "bilans",
            title: (
              <NavLink to="/bilans">
                <Space>
                  <HomeFilled />
                  Bilans
                </Space>
              </NavLink>
            ),
          },
          {
            key: "services-faits",
            title: "Services faits",
          },
        ]}
      />
      <Typography.Title level={1}>Bilans</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Services faits
      </Typography.Title>
      <Card title="Période concernée">
        <PeriodeField
          seulementPeriodesEnvoyees
          value={periode}
          onChange={(value) => setPeriode(value as IPeriode)}
        />
        <Space className="mt-1 legende">
          <InfoCircleFilled />
          <span>Seules les périodes déclarées comme envoyées à la RH peuvent être consultées</span>
        </Space>
      </Card>
      {periode && (
        <Card title="Services faits" className="mt-3">
          <ServicesFaitsItem periode={periode} />
        </Card>
      )}
    </Layout.Content>
  );
}
