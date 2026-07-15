/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useEffect, useState } from "react";
import { Breadcrumb, FloatButton, Layout, Space, Typography } from "antd";
import { NavLink, useParams } from "react-router-dom";
import { HomeFilled, PlusOutlined } from "@ant-design/icons";
import { useApi } from "@context/api/ApiProvider";
import Spinner from "@controls/Spinner/Spinner";
import { ICommission } from "@api";
import { CommissionsEdition } from "@controls/Admin/Commissions/CommissionsEdition";
import { CommissionsTable } from "@controls/Table/Admin/CommissionsTable";

export default function Commissions(): React.ReactElement {
  const [idChargement, setIdChargement] = useState<string | undefined>(useParams<"id">().id);
  const [order, setOrder] = useState<"asc" | "desc">("asc");
  const [editedItem, setEditedItem] = useState<ICommission>();
  const { data: commissions, isFetching } = useApi().useGetFullCollection({
    path: "/commissions",
    query: {
      "order[libelle]": order,
    },
  });

  useEffect(() => {
    if (idChargement && commissions) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setEditedItem(commissions.items.find((c) => c.id?.toString() === idChargement));

      setIdChargement(undefined);
    }
  }, [idChargement, commissions]);

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
                <Space>Demandes</Space>
              </NavLink>
            ),
          },
          {
            key: "commissions",
            title: "Commissions",
          },
        ]}
      />
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Commissions
      </Typography.Title>
      {isFetching && <Spinner />}
      {!isFetching && commissions && (
        <CommissionsTable
          commissions={commissions.items}
          editedItem={editedItem}
          setEditedItem={setEditedItem}
          setOrder={setOrder}
        />
      )}
      {editedItem && <CommissionsEdition editedItem={editedItem} setEditedItem={setEditedItem} />}
      <FloatButton
        icon={<PlusOutlined />}
        type="primary"
        tooltip="Ajouter une commission"
        onClick={() => {
          setEditedItem({} as ICommission);
        }}
      />
    </Layout.Content>
  );
}
