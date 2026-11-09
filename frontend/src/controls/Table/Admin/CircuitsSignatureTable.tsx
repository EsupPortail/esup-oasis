/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import React, { useState } from "react";
import { Button, Table, Tag, Typography } from "antd";
import { EditOutlined } from "@ant-design/icons";
import { useApi } from "@context/api/ApiProvider";
import { IComposante } from "@api";
import { CircuitSignatureEdition } from "@controls/Admin/SignatureElectronique/CircuitSignatureEdition";

/** Composantes et circuit de signature de leurs décisions. */
export default function CircuitsSignatureTable() {
  const [editedItem, setEditedItem] = useState<IComposante>();
  const { data: composantes, isFetching } = useApi().useGetFullCollection({
    path: "/composantes",
    query: { "order[libelle]": "asc" },
    itemsPerPage: 500,
    concurrency: 1,
  });

  return (
    <>
      <Table<IComposante>
        loading={isFetching}
        dataSource={composantes?.items}
        rowKey={(record) => record["@id"] as string}
        pagination={false}
        columns={[
          {
            title: "Composante",
            dataIndex: "libelle",
            className: "semi-bold",
            render: (value: string) => <Tag>{value}</Tag>,
          },
          {
            title: "Circuit de signature",
            dataIndex: "circuitSignature",
            render: (value: string | null | undefined) =>
              value ? (
                <span className="code">{value}</span>
              ) : (
                <Typography.Text type="secondary">Aucun : envoi par e-mail</Typography.Text>
              ),
          },
          {
            key: "actions",
            className: "text-right commandes",
            width: 150,
            render: (_, record) => (
              <Button icon={<EditOutlined />} onClick={() => setEditedItem(record)}>
                Éditer
              </Button>
            ),
          },
        ]}
      />
      {editedItem && (
        <CircuitSignatureEdition editedItem={editedItem} setEditedItem={setEditedItem} />
      )}
    </>
  );
}
