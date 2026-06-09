/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useApi } from "../../../context/api/ApiProvider";
import { NB_MAX_ITEMS_PER_PAGE } from "../../../constants";
import { Button, Table } from "antd";
import { EditOutlined } from "@ant-design/icons";
import React, { useState } from "react";
import { ITypeAmenagement } from "../../../api/ApiTypeHelpers";
import BooleanState from "../../State/BooleanState";
import { getDomaineAmenagement } from "../../../lib/amenagements";

interface TypesAmenagementsTableProps {
   categorieId: string;
   onEdit: (item: ITypeAmenagement) => void;
   afficherDesactives: boolean;
}

export function TypesAmenagementsTable({
                                          categorieId,
                                          onEdit,
                                          afficherDesactives,
                                       }: TypesAmenagementsTableProps) {
   const [order, setOrder] = useState<"asc" | "desc">("asc");
   const { data: typesAmenagements, isFetching } = useApi().useGetCollectionPaginated({
      path: "/types_amenagements",
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
      query: {
         "order[libelle]": order,
      },
   });

   return (
      <Table<ITypeAmenagement>
         rowKey={(record) => record["@id"] as string}
         className="table-responsive"
         loading={isFetching}
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
               title: "Type d'aménagement",
               dataIndex: "libelle",
               key: "libelle",
               sorter: true,
               defaultSortOrder: "ascend",
            },
            {
               title: "Domaine",
               dataIndex: "domaine",
               key: "domaine",
               render: (_type, record) => getDomaineAmenagement(record)?.singulier,
            },
            {
               title: "Actif",
               dataIndex: "actif",
               width: 120,
               className: "text-center",
               key: "actif",
               render: (actif) => (
                  <BooleanState value={actif} onLabel="Activé" offLabel="Désactivé" />
               ),
            },
            {
               title: "Décision",
               dataIndex: "decision",
               width: 120,
               className: "text-center",
               key: "decision",
               render: (decision) => (
                  <BooleanState value={decision} onLabel="Oui" offLabel="Non" tooltip="Présence dans la décision d'établissement"/>
               ),
            },
            {
               key: "actions",
               className: "text-right commandes",
               width: 150,
               render: (_, record) => (
                  <Button
                     icon={<EditOutlined />}
                     onClick={() => {
                        onEdit(record);
                     }}
                  >
                     Éditer
                  </Button>
               ),
            },
         ]}
         dataSource={[
            ...(typesAmenagements?.items || []).filter((t) => t.categorie === categorieId),
         ].filter((v) => v.actif || afficherDesactives)}
         pagination={false}
      />
   );
}
