/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Layout, Typography } from "antd";
import {
  AreaChartOutlined,
  BarChartOutlined,
  DotChartOutlined,
  RadarChartOutlined,
} from "@ant-design/icons";
import React from "react";
import { AdminPanel } from "@controls/Admin/AdminPanel";
import { useAuth } from "@/auth/AuthProvider";
import "@routes/administration/Administration.scss";
import { service } from "@lib";

/**
 * Generates the items for the BilansGestionnaire component.
 *
 * @returns {React.ReactElement} The items for the BilansGestionnaire component.
 */
export function BilansGestionnaireItems(): React.ReactElement {
  return (
    <div className="grid-admin">
      <AdminPanel
        title="Bénéficiaires"
        description="Suivi d’activités - Bénéficiaires"
        icon={BarChartOutlined}
        onClickUrl="/bilans/beneficiaires"
      />
      <AdminPanel
        title="Intervenants"
        description="Suivi d’activités - Intervenants"
        icon={BarChartOutlined}
        onClickUrl="/bilans/intervenants"
      />
    </div>
  );
}

/**
 * Renders the "Bilans" component.
 *
 * @returns {React.Element} The rendered "Bilans" component.
 */
export default function Bilans(): React.ReactElement {
  const user = useAuth().user;
  return (
    <Layout.Content className="administration" style={{ padding: "0 50px" }}>
      <Typography.Title level={1}>Bilans</Typography.Title>

      {user?.isAdmin && (
        <>
          <Typography.Title level={2}>Bilans administrateur</Typography.Title>
          <div className="grid-admin mb-1">
            <AdminPanel
              title="Services faits"
              description="Bilan envoyé à la RH"
              icon={DotChartOutlined}
              onClickUrl="/administration/bilans/services-faits"
            />
            <AdminPanel
              title="Bilan financier d'aide humaine"
              description="Intervenants avec coût chargé par période"
              icon={AreaChartOutlined}
              onClickUrl="/administration/bilans/financier"
            />
            <AdminPanel
              title={`Bilan d'activité ${service.sigle} univ.`}
              description={`Bilan d'activité ${service.sigle} universitaire`}
              icon={RadarChartOutlined}
              onClickUrl="/administration/bilans/activites"
            />
          </div>
        </>
      )}

      <Typography.Title level={2}>Suivi de l'activité</Typography.Title>
      <BilansGestionnaireItems />
    </Layout.Content>
  );
}
