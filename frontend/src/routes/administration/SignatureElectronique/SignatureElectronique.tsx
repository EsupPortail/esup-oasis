/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import React, { ReactElement } from "react";
import { Breadcrumb, Descriptions, Layout, Space, Typography } from "antd";
import { NavLink } from "react-router";
import { HomeFilled } from "@ant-design/icons";
import { env } from "@/env";
import { useApi } from "@context/api/ApiProvider";
import { decisionEtab, parapheurConfigure } from "@lib";
import { PARAMETRE_FREQUENCE_SUIVI_SIGNATURES } from "@/constants";
import CircuitsSignatureTable from "@controls/Table/Admin/CircuitsSignatureTable";

/**
 * Page d'administration de la signature électronique : le parapheur utilisé, la fréquence du suivi et le
 * circuit de chaque composante.
 *
 * @returns {ReactElement} The content to be rendered.
 */
export default function SignatureElectronique(): ReactElement {
  const { data: frequence } = useApi().useGetItem({
    path: "/parametres/{cle}",
    url: PARAMETRE_FREQUENCE_SUIVI_SIGNATURES,
    enabled: parapheurConfigure(),
  });

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
            key: "parametres",
            title: (
              <NavLink to="/administration#parametres">
                <Space>Paramètres</Space>
              </NavLink>
            ),
          },
          {
            key: "signature-electronique",
            title: "Signature électronique",
          },
        ]}
      />
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Signature électronique
      </Typography.Title>
      {parapheurConfigure() ? (
        <>
          <Descriptions
            bordered
            size="small"
            column={1}
            className="mb-3"
            items={[
              {
                key: "parapheur",
                label: "Parapheur",
                children: <span className="code">{env.REACT_APP_PARAPHEUR}</span>,
              },
              {
                key: "frequence",
                label: "Fréquence de suivi",
                children: (
                  <Space>
                    <span className="code">{frequence?.valeursCourantes?.[0]?.valeur ?? "–"}</span>
                    <NavLink to="/administration/referentiels/parametres">
                      Modifier dans les paramètres
                    </NavLink>
                  </Space>
                ),
              },
            ]}
          />
          <Typography.Paragraph>
            {decisionEtab.Defini} d'un bénéficiaire part dans le circuit de la composante de son
            inscription principale en cours. Une composante sans circuit garde l'envoi par e-mail.
          </Typography.Paragraph>
          <CircuitsSignatureTable />
        </>
      ) : (
        <Typography.Paragraph>
          Aucun parapheur n'est configuré : la signature électronique est désactivée.
        </Typography.Paragraph>
      )}
    </Layout.Content>
  );
}
