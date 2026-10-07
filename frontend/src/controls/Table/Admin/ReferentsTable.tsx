/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Button, Flex, Popconfirm, Space, Table, Tag, Tooltip } from "antd";
import { EditOutlined, MinusOutlined, UserSwitchOutlined } from "@ant-design/icons";
import { useApi } from "@context/api/ApiProvider";
import { IComposante, IComposanteQuery, IUtilisateur } from "@api";
import { ColumnsType } from "antd/lib/table";
import { useNavigate } from "react-router";
import { GestionnaireItem } from "@controls/Items/GestionnaireItem";
import { env } from "@/env";

interface TableReferentsProps {
  onEdit: (item: IComposante) => void;
}

type FiltreReferent = IComposanteQuery & {
  referents?: string;
  libelle?: string;
};

export default function ReferentsTable({ onEdit }: TableReferentsProps) {
  const [filtre, setFiltre] = React.useState<FiltreReferent>({
    page: 1,
    itemsPerPage: 25,
    "order[libelle]": "asc",
  });
  const navigate = useNavigate();

  const { data: composantes, isFetching } = useApi().useGetCollection({
    path: `/composantes`,
    query: filtre,
  });

  return (
    <>
      <Table<IComposante>
        dataSource={composantes?.items}
        className="table-responsive"
        loading={isFetching}
        style={{ overflow: "auto" }}
        rowKey={(record) => record["@id"] as string}
        pagination={{
          current: filtre?.page,
          pageSize: filtre?.itemsPerPage,
          total: composantes?.totalItems,
          showSizeChanger: true,
          pageSizeOptions: ["25", "50", "100", "250", "500"],
          onChange: (p, ps) => {
            setFiltre({ ...filtre, page: p, itemsPerPage: ps });
          },
          showTotal: (total, range) => (
            <div className="text-legende mr-1">
              {range[0]} à {range[1]} / {total}
            </div>
          ),
        }}
        onChange={(_pagination, filters, sorter) => {
          // tri
          if (Array.isArray(sorter)) {
            setFiltre({
              ...filtre,
              "order[libelle]": sorter[0].order === "ascend" ? "asc" : "desc",
              referents: filters?.referents?.[0] as string | undefined,
            });
          } else {
            setFiltre({
              ...filtre,
              "order[libelle]": sorter.order === "ascend" ? "asc" : "desc",
              referents: filters?.referents?.[0] as string | undefined,
            });
          }
        }}
        columns={
          [
            {
              title: "Composante",
              dataIndex: "libelle",
              className: "semi-bold",
              render: (value: string) => <Tag>{value}</Tag>,
            },

            {
              title: "Référent•es",
              dataIndex: "referents",
              render: (values: string[]) => {
                if (!values || values.length === 0) {
                  return <MinusOutlined />;
                }
                return (
                  <Space orientation="vertical" size={2} className="w-100">
                    {values.map((value) => (
                      <Flex key={value} justify="space-between" className="w-100">
                        <GestionnaireItem gestionnaireId={value} />
                        {env.REACT_APP_ENVIRONMENT !== "production" && (
                          <Popconfirm
                            title="Êtes-vous sûr de vouloir prendre l'identité de cet utilisateur ?"
                            onConfirm={() => {
                              navigate(
                                `/impersonate/${value.replace(`${env.REACT_APP_API_PREFIX}/utilisateurs/`, "")}`,
                              );
                            }}
                            okText="Oui"
                            cancelText="Non"
                            placement="left"
                          >
                            <Tooltip title="Prendre l'identité">
                              <Button icon={<UserSwitchOutlined />} />
                            </Tooltip>
                          </Popconfirm>
                        )}
                      </Flex>
                    ))}
                  </Space>
                );
              },
            },

            {
              dataIndex: "commandes",
              width: 150,
              render: (_value: unknown, record: IUtilisateur) => (
                <Space>
                  <Button
                    icon={<EditOutlined />}
                    onClick={() => {
                      onEdit(record);
                    }}
                  >
                    Éditer
                  </Button>
                </Space>
              ),
            },
          ].filter((c) => c !== null) as ColumnsType<IComposante>
        }
      />
    </>
  );
}
