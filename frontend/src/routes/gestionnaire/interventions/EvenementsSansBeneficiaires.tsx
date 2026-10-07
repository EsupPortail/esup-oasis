/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import "@routes/administration/Administration.scss";
import { useApi } from "@context/api/ApiProvider";
import CalendarTable from "@controls/Calendar/Table/CalendarTable";
import { Evenement } from "@lib";
import { Alert, Breadcrumb, Layout, Space, Typography } from "antd";
import { NavLink } from "react-router";
import { HomeFilled, InfoCircleFilled } from "@ant-design/icons";

/**
 * Renders the page for ROLE_GESTIONNAIRE to manage his own interventions.
 */
export default function EvenementsSansBeneficiaires() {
  const { data } = useApi().useGetFullCollection({
    path: "/evenements",
    query: {
      "exists[beneficiaires]": false,
      "type.avecValidation": false,
      "type.forfait": false,
    },
  });

  return (
    <Layout.Content style={{ padding: "0 50px" }}>
      <Typography.Title level={1}>Évènements sans bénéficaire</Typography.Title>

      <Breadcrumb
        className="mt-2"
        items={[
          {
            key: "planning",
            title: (
              <NavLink to="/planning">
                <Space>
                  <HomeFilled />
                  Planning
                </Space>
              </NavLink>
            ),
          },
          {
            key: "sans-beneficiaires",
            title: (
              <Typography.Text type="secondary" className="ml-1">
                Évènements sans bénéficiaire
              </Typography.Text>
            ),
          },
        ]}
      />

      <Alert
        icon={<InfoCircleFilled />}
        showIcon
        className="mt-2"
        type="info"
        title="Information"
        description={
          <>
            Cette page liste les évènements sans bénéficiaire, il s'agit d'un état qui ne devrait
            pas se produire dans l'application.
            <br />
            <br />
            Tant que la période n'a pas été envoyée à la RH, ces évènements peuvent être corrigés.{" "}
            <b className="semi-bold">
              Si vous êtes à l'origine de cet évènement, merci de contacter l'administrateur de
              l'application afin de corriger l'erreur qui est à l'origine de ce problème.
            </b>
          </>
        }
      />

      <CalendarTable
        events={(data?.items || []).map((e) => new Evenement(e))}
        saisieEvtRenfort={false}
      />
    </Layout.Content>
  );
}
