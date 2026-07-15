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
import { FileOutlined, HomeFilled, PlusOutlined, UserOutlined } from "@ant-design/icons";
import { ISportifHautNiveau } from "@api";
import { SportifsHautNiveauTable } from "@controls/Table/Admin/SportifsHautNiveauTable";
import { SportifsHautNiveauEdition } from "@controls/Admin/Referentiel/SportifsHautNiveau/SportifsHautNiveauEdition";
import { useAuth } from "@/auth/AuthProvider";
import { SportifsHautNiveauImport } from "@controls/Admin/Referentiel/SportifsHautNiveau/SportifsHautNiveauImport";

function AddFloatButton(props: {
  setEditedItem: (value: ISportifHautNiveau | undefined) => void;
  setImportFichier: (value: boolean) => void;
}) {
  const user = useAuth().user;

  if (user?.isAdminTechnique) {
    return (
      <FloatButton.Group
        trigger="click"
        type="primary"
        tooltip="Ajouter un ou plusieurs sportifs haut niveau"
        icon={<PlusOutlined />}
        style={{ right: 60, bottom: 40 }}
      >
        <FloatButton
          className="float-button-fix"
          icon={<FileOutlined />}
          tooltip="Déposer un fichier de sportifs haut niveau"
          onClick={() => {
            props.setImportFichier(true);
          }}
        />
        <FloatButton
          onClick={() => {
            props.setEditedItem({} as ISportifHautNiveau);
          }}
          icon={<UserOutlined />}
          tooltip="Ajouter un sportif haut niveau"
        />
      </FloatButton.Group>
    );
  }

  return (
    <FloatButton
      onClick={() => {
        props.setEditedItem({} as ISportifHautNiveau);
      }}
      icon={<UserOutlined />}
      tooltip="Ajouter un sportif haut niveau"
    />
  );
}

/**
 * Renders admin page for managing sportifs haut niveau
 *
 * @returns {ReactElement} The rendered component.
 */
export default function SportifsHautNiveau(): ReactElement {
  const [editedItem, setEditedItem] = useState<ISportifHautNiveau | undefined>();
  const [importFichier, setImportFichier] = React.useState<boolean>(false);

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
            key: "demandes",
            title: (
              <NavLink to="/administration#demandes">
                <Space>Demandeurs</Space>
              </NavLink>
            ),
          },
          {
            key: "sportfsHautNiveau",
            title: "Sportifs Haut Niveau",
          },
        ]}
      />{" "}
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Sportifs Haut Niveau
      </Typography.Title>
      <Row gutter={[16, 16]}>
        <Col span={24}>
          <SportifsHautNiveauTable editedItem={editedItem} onEdit={(s) => setEditedItem(s)} />
        </Col>
      </Row>
      {editedItem && (
        <SportifsHautNiveauEdition editedItem={editedItem} setEditedItem={setEditedItem} />
      )}
      {importFichier && <SportifsHautNiveauImport setOpen={setImportFichier} />}
      <AddFloatButton setEditedItem={setEditedItem} setImportFichier={setImportFichier} />
    </Layout.Content>
  );
}
