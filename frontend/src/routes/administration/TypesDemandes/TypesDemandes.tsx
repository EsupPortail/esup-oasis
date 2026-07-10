/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useState } from "react";
import { Badge, Breadcrumb, Button, Layout, Space, Table, Tabs, Tooltip, Typography } from "antd";
import { NavLink } from "react-router-dom";
import { CaretRightFilled, EditOutlined, HomeFilled } from "@ant-design/icons";
import { useApi } from "@context/api/ApiProvider";
import Spinner from "@controls/Spinner/Spinner";
import { ITypeDemande } from "@api";
import BooleanState from "@controls/State/BooleanState";
import { ProfilItem } from "@controls/Items/ProfilItem";
import { TypesDemandesEdition } from "@controls/Admin/TypesDemandes/TypesDemandesEdition";
import { Campagne } from "@controls/Admin/TypesDemandes/Campagne";
import TypesDemandesCampagnes from "./TypesDemandesCampagnes";

export default function TypesDemandes(): React.ReactElement {
  const [order, setOrder] = useState<"asc" | "desc">("asc");
  const [expandedRowKeys, setExpandedRowKeys] = useState<string[]>([]);
  const [editedItem, setEditedItem] = useState<ITypeDemande>();
  const { data: typesDemandes, isFetching } = useApi().useGetFullCollection({
    path: "/types_demandes",
    query: {
      "order[libelle]": order,
    },
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
            key: "demandes",
            title: (
              <NavLink to="/administration#demandes">
                <Space>Demandes</Space>
              </NavLink>
            ),
          },
          {
            key: "types_demandes",
            title: "Campagnes de demande",
          },
        ]}
      />
      <Typography.Title level={1}>Administration</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Campagnes de demande
      </Typography.Title>
      {isFetching && <Spinner />}
      {!isFetching && typesDemandes && (
        <Table<ITypeDemande>
          dataSource={typesDemandes.items}
          rowKey={(record) => record["@id"] as string}
          rowClassName={(record) => {
            if (!record.actif) {
              return "ref-inactif";
            }
            return record["@id"] === editedItem?.["@id"]
              ? "bg-primary-light shadow-1 border-0"
              : "";
          }}
          onChange={(_pagination, _filters, sorter) => {
            // tri
            if (Array.isArray(sorter)) {
              setOrder(sorter[0].order === "ascend" ? "asc" : "desc");
            } else {
              setOrder(sorter.order === "ascend" ? "asc" : "desc");
            }
          }}
          columns={[
            {
              title: "Type de demande",
              dataIndex: "libelle",
              key: "libelle",
              render: (text, record) => (
                <>
                  {record.campagneEnCours && (
                    <Tooltip title="Campagne en cours">
                      <CaretRightFilled className="float-right mr-1" style={{ marginTop: 3 }} />
                    </Tooltip>
                  )}
                  <Typography.Text
                    className={
                      expandedRowKeys.includes(record["@id"] as string)
                        ? "semi-bold text-primary"
                        : ""
                    }
                  >
                    {text}
                  </Typography.Text>
                </>
              ),
              sorter: true,
              defaultSortOrder: "ascend",
            },
            {
              title: "État",
              dataIndex: "actif",
              key: "actif",
              render: (actif) => (
                <BooleanState value={actif} onLabel="Activé" offLabel="Désactivé" />
              ),
            },
            {
              title: "Profils",
              dataIndex: "profilsCibles",
              key: "profilsCibles",
              render: (_item, record) => {
                return record.profilsCibles?.map((profil) => (
                  <ProfilItem key={profil} profil={profil} />
                ));
              },
            },
            {
              dataIndex: "commandes",
              key: "commandes",
              className: "text-right commandes",
              render: (_item, record) => (
                <Button
                  icon={<EditOutlined />}
                  onClick={(event) => {
                    event.stopPropagation();
                    setEditedItem(record);
                  }}
                >
                  Éditer
                </Button>
              ),
            },
          ]}
          expandable={{
            expandRowByClick: true,
            expandedRowKeys,
            onExpand: (expanded, record) => {
              if (expanded) {
                setExpandedRowKeys([record["@id"] as string]);
              } else {
                setExpandedRowKeys([]);
              }
            },
            expandedRowRender: (record) => {
              return (
                <div className="btb-1">
                  <Tabs
                    className="campagnes-tabs"
                    defaultActiveKey="1"
                    type="card"
                    items={[
                      {
                        key: "en-cours",
                        label: (
                          <Space size="small">
                            <span>Campagne en cours</span>
                            {record.campagneEnCours && <Badge dot />}
                          </Space>
                        ),
                        children: (
                          <Campagne
                            title="Campagne en cours"
                            typeDemandeId={record["@id"] as string}
                            campagneId={record.campagneEnCours}
                            showError
                          />
                        ),
                      },
                      {
                        key: "suivante",
                        label: (
                          <Space size="small">
                            <span>Campagne suivante</span>{" "}
                            {record.campagneSuivante && <Badge dot />}
                          </Space>
                        ),
                        children: (
                          <Campagne
                            title="Campagne suivante"
                            typeDemandeId={record["@id"] as string}
                            campagneId={record.campagneSuivante}
                            showError={false}
                          />
                        ),
                      },
                      {
                        key: "toutes",
                        label: "Toutes les campagnes",
                        children: (
                          <TypesDemandesCampagnes typeDemandeId={record["@id"] as string} />
                        ),
                      },
                    ]}
                  />
                </div>
              );
            },
          }}
        />
      )}
      {editedItem && <TypesDemandesEdition editedItem={editedItem} setEditedItem={setEditedItem} />}
    </Layout.Content>
  );
}
