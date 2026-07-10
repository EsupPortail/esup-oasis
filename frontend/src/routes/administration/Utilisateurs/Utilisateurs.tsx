/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useState } from "react";
import { Breadcrumb, Col, Drawer, FloatButton, Layout, Row, Space, Typography } from "antd";
import "@routes/administration/Administration.scss";
import { HomeFilled, PlusOutlined } from "@ant-design/icons";
import { NavLink } from "react-router-dom";
import { UtilisateurSearch } from "@controls/Search/UtilisateurSearch";
import DashboardUtilisateurStats from "@controls/Dashboard/DashboardUtilisateurStats";
import { UtilisateurEditer } from "@controls/Admin/Utilisateurs/UtilisateurEditer";
import UtilisateursTable from "@controls/Table/Admin/UtilisateursTable";
import { IUtilisateur } from "@api";
import { env } from "@/env";

/**
 * Renders the user administration page.
 *
 * @returns {ReactElement} The rendered user administration page.
 */
export default function Utilisateurs(): ReactElement {
  const [searchUser, setSearchUser] = useState(false);
  const [statsItem, setStatsItem] = useState<IUtilisateur | undefined>();
  const [editedItem, setEditedItem] = useState<IUtilisateur | undefined>();

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
            key: "users",
            title: (
              <NavLink to="/administration#utilisateurs">
                <Space>Utilisateurs</Space>
              </NavLink>
            ),
          },
          {
            key: "utilisateurs",
            title: "Utilisateurs de l'application",
          },
        ]}
      />{" "}
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Utilisateurs
      </Typography.Title>
      <FloatButton
        onClick={() => {
          setSearchUser(true);
        }}
        icon={<PlusOutlined />}
        type="primary"
        tooltip={`Ajouter un utilisateur ${env.REACT_APP_SERVICE}`}
      />
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <UtilisateursTable onEdit={setEditedItem} onAskStats={setStatsItem} />
        </Col>
        {editedItem && (
          <Drawer
            open
            onClose={() => setEditedItem(undefined)}
            title="Édition de l'utilisateur"
            size="large"
          >
            <UtilisateurEditer utilisateur={editedItem} onEdited={() => setEditedItem(undefined)} />
          </Drawer>
        )}
        {statsItem && (
          <Drawer
            open
            onClose={() => setStatsItem(undefined)}
            title="Notifications de l'utilisateur"
            size="large"
          >
            <DashboardUtilisateurStats utilisateurId={statsItem["@id"] as string} />
          </Drawer>
        )}
        {searchUser && (
          <UtilisateurSearch
            visible={searchUser}
            setVisible={setSearchUser}
            onSelected={(userSelected) => {
              setSearchUser(false);
              setEditedItem(userSelected);
            }}
          />
        )}
      </Row>
    </Layout.Content>
  );
}
