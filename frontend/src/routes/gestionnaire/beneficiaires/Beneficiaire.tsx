/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Breadcrumb, Layout, Space, Typography } from "antd";
import { NavLink, useParams } from "react-router";
import DossierBeneficiaire from "@controls/Description/DossierBeneficiaire";
import { useApi } from "@context/api/ApiProvider";
import { HomeFilled } from "@ant-design/icons";

/**
 * Renders the page for ROLE_GESTIONNAIRE to manage beneficiaries.
 *
 * @returns {ReactElement} The rendered Beneficiaires component.
 */
export default function Beneficiaire(): ReactElement {
  const { id } = useParams<"id">();
  const { data: beneficiaire } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: `/utilisateurs/${id}`,
    enabled: !!id,
  });

  if (!beneficiaire) {
    return (
      <Layout.Content className="beneficiaires" style={{ padding: "0 50px" }}>
        <Typography.Title level={1}>Bénéficiaire</Typography.Title>
        <Typography.Paragraph>Aucun bénéficiaire sélectionné.</Typography.Paragraph>
      </Layout.Content>
    );
  }

  return (
    <Layout.Content className="beneficiaires" style={{ padding: "0 50px" }}>
      <Typography.Title level={1}>Bénéficiaire</Typography.Title>
      <Breadcrumb
        className="mt-2 mb-2"
        items={[
          {
            key: "beneficiaires",
            title: (
              <NavLink to="/beneficiaires">
                <Space>
                  <HomeFilled />
                  Bénéficaires
                </Space>
              </NavLink>
            ),
          },
          {
            key: "beneficiaire",
            title: (
              <>
                {beneficiaire.prenom} {beneficiaire.nom?.toLocaleUpperCase()}
              </>
            ),
          },
        ]}
      />
      <DossierBeneficiaire beneficiaireId={`/utilisateurs/${id}`} />
    </Layout.Content>
  );
}
