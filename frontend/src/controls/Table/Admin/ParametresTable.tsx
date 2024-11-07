/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useState } from "react";
import { Button, Space, Table } from "antd";
import { useApi } from "../../../context/api/ApiProvider";
import { NB_MAX_ITEMS_PER_PAGE } from "../../../constants";
import { EditOutlined, FileOutlined } from "@ant-design/icons";
import ParametresEdition from "../../Admin/Parametres/ParametresEdition";
import { IParametre, IParametreValeur } from "../../../api/ApiTypeHelpers";

export default function ParametresTable() {
   const [editedItem, setEditedItem] = useState<IParametre>();
   const { data: parametres, isFetching: isFetchingParametres } =
      useApi().useGetCollectionPaginated({
         path: "/parametres",
         page: 1,
         itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
      });

   return (
      <>
         <Table<IParametre>
            loading={isFetchingParametres}
            dataSource={parametres?.items as IParametre[]}
            rowKey={(record) => record["@id"] as string}
            pagination={{
               total: parametres?.totalItems,
               pageSize: NB_MAX_ITEMS_PER_PAGE,
               showSizeChanger: false,
            }}
            columns={[
               {
                  title: "Nom du paramètre",
                  dataIndex: "cle",
                  key: "cle",
               },
               {
                  title: "Valeur courante",
                  dataIndex: "valeursCourantes",
                  key: "valeursCourantes",
                  render: (values: IParametreValeur[], record: IParametre) => {
                     if (record.fichier) {
                        return (
                           <Space
                              key={record["@id"]}
                              className="code"
                              style={{ display: "inline-flex" }}
                           >
                              <FileOutlined />
                              <span className="italic">
                                 [{values.length} fichier{values.length > 1 ? "s" : ""}]
                              </span>
                           </Space>
                        );
                     }

                     return (
                        <Space wrap>
                           {values.map((v) => {
                              return (
                                 <pre key={v["@id"]} className="code">
                                    {v.valeur}
                                 </pre>
                              );
                           })}
                        </Space>
                     );
                  },
               },
               {
                  key: "actions",
                  className: "text-right commandes",
                  width: 150,
                  render: (_, record) => (
                     <Button
                        icon={<EditOutlined />}
                        onClick={() => {
                           setEditedItem(record);
                        }}
                     >
                        Éditer
                     </Button>
                  ),
               },
            ]}
         />
         {editedItem && (
            <ParametresEdition
               parametreId={editedItem["@id"] as string}
               onClose={() => setEditedItem(undefined)}
            />
         )}
      </>
   );
}
