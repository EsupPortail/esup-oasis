/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Layout, Skeleton, Space, Tabs, Typography } from "antd";
import { useParams } from "react-router";
import { FileDoneOutlined, UserOutlined } from "@ant-design/icons";
import { useApi } from "@context/api/ApiProvider";
import { TabIdentite } from "@controls/TabsContent/TabIdentite";
import DemandesBadge from "@controls/Badge/DemandesBadge";
import { TabDemandes } from "@controls/TabsContent/TabDemandes";

export default function Demandeur(): ReactElement {
  const { id } = useParams<"id">();
  const { data: demandeur } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: `/utilisateurs/${id}`,
    enabled: !!id,
  });

  if (!id) {
    return (
      <Layout.Content className="demandeurs" style={{ padding: "0 50px" }}>
        <Typography.Title level={1}>Demandeur</Typography.Title>
        <Typography.Paragraph>Aucun bénéficiaire sélectionné.</Typography.Paragraph>
      </Layout.Content>
    );
  }

  return (
    <Layout.Content className="demandes" style={{ padding: "0 50px" }}>
      <Typography.Title level={1}>Demandeur</Typography.Title>
      {demandeur ? (
        <Typography.Title level={2}>
          {demandeur?.prenom} {demandeur?.nom?.toLocaleUpperCase()}
        </Typography.Title>
      ) : null}
      {demandeur ? (
        <Tabs
          type="card"
          rootClassName="tabs-tab-card"
          items={[
            {
              key: "identite",
              label: "Identité",
              children: <TabIdentite utilisateurId={demandeur["@id"] as string} />,
              icon: <UserOutlined />,
            },
            {
              key: "demandes",
              label: (
                <Space>
                  Demandes
                  <DemandesBadge utilisateurId={demandeur["@id"] as string} />
                </Space>
              ),
              children: (
                <TabDemandes
                  utilisateur={demandeur}
                  title={
                    <Typography.Title level={3} className="mt-1">
                      Demandes déposées
                    </Typography.Title>
                  }
                />
              ),
              icon: <FileDoneOutlined />,
            },
          ]}
        />
      ) : (
        <Skeleton paragraph={{ rows: 4 }} active />
      )}
    </Layout.Content>
  );
}
