/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useEffect, useState } from "react";
import RoleCalculeItem from "../../Items/RoleCalculeItem";
import ServiceItem from "../../Items/ServiceItem";
import { Button, Flex, Popconfirm, Segmented, Space, Table, Tooltip } from "antd";
import { BellOutlined, EditOutlined, UserSwitchOutlined } from "@ant-design/icons";
import { useApi } from "../../../context/api/ApiProvider";
import { setAffichageFiltres } from "../../../redux/actions/AffichageFiltre";
import { initialAffichageFiltres } from "../../../redux/context/IAffichageFiltres";
import { queryClient } from "../../../App";
import { useAuth } from "../../../auth/AuthProvider";
import { useDispatch } from "react-redux";
import { ROLES_SELECT, RoleValues } from "../../../lib/Utilisateur";
import { IUtilisateur } from "../../../api/ApiTypeHelpers";
import { ColumnsType } from "antd/lib/table";
import { useNavigate } from "react-router-dom";
import { FilterProps } from "../../../utils/table";
import Highlighter from "react-highlight-words";
import { removeAccents } from "../../../utils/string";
import { env } from "../../../env";

interface TableUtilisateursProps {
   onEdit: (item: IUtilisateur) => void;
   onAskStats: (item: IUtilisateur) => void;
}

type FiltreUtilisateurs = {
   nom?: string;
   "order[nom]"?: "asc" | "desc";
};

export default function UtilisateursTable({ onEdit, onAskStats }: TableUtilisateursProps) {
   const dispatch = useDispatch();
   const auth = useAuth();
   const [hasImpersonate, setHasImpersonate] = useState(false);
   const [role, setRole] = useState<RoleValues>(RoleValues.ROLE_PLANIFICATEUR);
   const [domaine, setDomaine] = useState(RoleValues.ROLE_PLANIFICATEUR);
   const [filtre, setFiltre] = React.useState<FiltreUtilisateurs>({
      "order[nom]": "asc",
   });
   const [page, setPage] = useState(1);
   const [itemsPerPage, setItemsPerPage] = useState(100);
   const navigate = useNavigate();

   const { data, isFetching } = useApi().useGetCollectionPaginated({
      path: `/roles/{roleId}/utilisateurs`,
      page: page,
      itemsPerPage: itemsPerPage,
      parameters: {
         roleId: `/roles/${role}`,
      },
      query: filtre,
   });

   useEffect(() => {
      if (auth.impersonate && hasImpersonate) {
         dispatch(
            setAffichageFiltres(initialAffichageFiltres.affichage, initialAffichageFiltres.filtres),
         );
         queryClient.clear();
      }
   }, [hasImpersonate, auth.impersonate, dispatch]);

   useEffect(() => {
      if (role !== RoleValues.ROLE_MEMBRE_COMMISSION) {
         setDomaine(RoleValues.ROLE_PLANIFICATEUR);
      }
   }, [role]);

   return (
      <>
         <Flex justify="flex-end" align="center">
            <Space>
               <span>Membre de :</span>
               <Segmented
                  value={domaine}
                  onChange={(value) => {
                     setDomaine(value);
                     setRole(value);
                  }}
                  options={[
                     {
                        value: RoleValues.ROLE_PLANIFICATEUR,
                        label: `Service ${env.REACT_APP_SERVICE}`,
                     },
                     {
                        value: RoleValues.ROLE_MEMBRE_COMMISSION,
                        label: "Commission",
                     },
                  ]}
               />
            </Space>
         </Flex>
         <Table<IUtilisateur>
            dataSource={data?.items}
            className="table-responsive"
            loading={isFetching}
            style={{ overflow: "auto" }}
            rowKey={(record) => record["@id"] as string}
            pagination={{
               current: page,
               pageSize: itemsPerPage,
               total: data?.totalItems,
               showSizeChanger: true,
               pageSizeOptions: ["25", "50", "100", "250", "500"],
               onChange: (p, ps) => {
                  setPage(p);
                  setItemsPerPage(ps);
               },
               showTotal: (total, range) => (
                  <div className="text-legende mr-1">
                     {range[0]} à {range[1]} / {total}
                  </div>
               ),
            }}
            onChange={(_pagination, filters, sorter) => {
               if (filters.roles) {
                  setRole(filters.roles[0] as RoleValues);
               } else {
                  setRole(RoleValues.ROLE_PLANIFICATEUR);
               }

               // tri
               if (Array.isArray(sorter)) {
                  setFiltre({
                     ...filtre,
                     "order[nom]": sorter[0].order === "ascend" ? "asc" : "desc",
                  });
               } else {
                  setFiltre({
                     ...filtre,
                     "order[nom]": sorter.order === "ascend" ? "asc" : "desc",
                  });
               }
            }}
            columns={
               [
                  {
                     title: "Nom",
                     dataIndex: "nom",
                     sorter: true,
                     defaultSortOrder: "ascend",
                     className: "semi-bold",
                     ...FilterProps<FiltreUtilisateurs>("nom", filtre, setFiltre),
                     filteredValue: filtre?.nom ? [filtre?.nom] : null,
                     render: (value: string) => (
                        <Highlighter
                           searchWords={[removeAccents(filtre.nom || "")]}
                           textToHighlight={value.toLocaleUpperCase()}
                        />
                     ),
                  },
                  {
                     title: "Prénom",
                     dataIndex: "prenom",
                  },
                  {
                     title: "Rôle",
                     dataIndex: "roles",
                     render: (value: string[]) => {
                        return <RoleCalculeItem roles={value} />;
                     },
                     filters: ROLES_SELECT.map((r) => ({
                        text: r.label,
                        value: r.value,
                     })),
                     filterMultiple: false,
                     filterSearch: true,
                     filteredValue:
                        role === RoleValues.ROLE_PLANIFICATEUR ||
                        role === RoleValues.ROLE_MEMBRE_COMMISSION
                           ? null
                           : [role],
                  },
                  role === RoleValues.ROLE_MEMBRE_COMMISSION
                     ? null
                     : {
                          title: `Bureaux ${env.REACT_APP_SERVICE}`,
                          dataIndex: "services",
                          responsive: ["xl"],
                          render: (value: string[]) => {
                             return <ServiceItem services={value} />;
                          },
                       },
                  {
                     dataIndex: "commandes",
                     width: 150,
                     render: (_value: unknown, record: IUtilisateur) => (
                        <Space>
                           {domaine === RoleValues.ROLE_PLANIFICATEUR && (
                              <Button
                                 icon={<BellOutlined />}
                                 onClick={() => {
                                    onAskStats(record);
                                 }}
                              />
                           )}
                           {env.REACT_APP_ENVIRONMENT !== "production" && (
                              <Popconfirm
                                 title="Êtes-vous sûr de vouloir prendre l'identité de cet utilisateur ?"
                                 onConfirm={() => {
                                    navigate("/");
                                    window.setTimeout(() => {
                                       setHasImpersonate(true);
                                       auth.setImpersonate(record.uid as string);
                                    }, 500);
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
                           {record.roles?.includes(RoleValues.ROLE_PLANIFICATEUR) && (
                              <Button
                                 icon={<EditOutlined />}
                                 onClick={() => {
                                    onEdit(record);
                                 }}
                              />
                           )}
                        </Space>
                     ),
                  },
               ].filter((c) => c !== null) as ColumnsType<IUtilisateur>
            }
         />
      </>
   );
}
