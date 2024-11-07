/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useApi } from "../../context/api/ApiProvider";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { App, Button, Flex, List, Space, Typography } from "antd";
import React from "react";
import { IDocumentBeneficiaire } from "../../api/ApiTypeHelpers";
import { FileOutlined, PlusOutlined } from "@ant-design/icons";
import { ModalDocument } from "../Modals/ModalDocument";
import dayjs from "dayjs";
import EtudiantItem from "../Items/EtudiantItem";
import { Fichier } from "../Fichier/Fichier";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";

function DocumentList(props: {
   documents: IDocumentBeneficiaire[];
   setEditedItem?: (item: IDocumentBeneficiaire) => void;
   utilisateurId: string;
}) {
   const { message } = App.useApp();
   const mutationDelete = useApi().useDelete({
      path: "/beneficiaires/{uid}/pieces_jointes/{id}",
      onSuccess: () => {
         message.success("Document supprimé").then();
      },
      invalidationQueryKeys: [props.utilisateurId, "/beneficiaires/{uid}/pieces_jointes"],
   });

   return (
      <List className="ant-list-radius">
         {props.documents
            .sort((d1, d2) => {
               return dayjs(d2.dateDepot).diff(dayjs(d1.dateDepot));
            })
            .map((document) => (
               <List.Item key={document["@id"]}>
                  <List.Item.Meta
                     title={document.libelle}
                     description={
                        <Space direction="vertical" className="w-100">
                           <Space direction="horizontal" size={4}>
                              Déposé le {dayjs(document.dateDepot).format("DD/MM/YYYY")} par{" "}
                              <EtudiantItem
                                 utilisateurId={document.utilisateurCreation}
                                 showAvatar={false}
                              />
                           </Space>
                           <Fichier
                              fichierId={document.fichier as string}
                              onRemove={() => {
                                 mutationDelete.mutate({
                                    "@id": document["@id"] as string,
                                 });
                              }}
                           />
                        </Space>
                     }
                     avatar={<FileOutlined />}
                  />
               </List.Item>
            ))}
      </List>
   );
}

export function TabDocuments(props: { utilisateurId: string }) {
   const [editedItem, setEditedItem] = React.useState<IDocumentBeneficiaire>();
   const screens = useBreakpoint();

   const { data: documents } = useApi().useGetCollectionPaginated({
      path: "/beneficiaires/{uid}/pieces_jointes",
      parameters: {
         uid: props.utilisateurId.replace("/utilisateurs/", "/beneficiaires/"),
      },
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
   });

   return (
      <>
         <Flex justify="space-between" align="center" className="mt-1 mb-2" wrap>
            <Typography.Title level={3} className="mt-0 mb-0">
               Documents
            </Typography.Title>
            <div className={`text-right ${!screens.lg ? "mt-2" : ""}`}>
               {editedItem && (
                  <ModalDocument
                     open
                     setOpen={(open) => {
                        if (!open) setEditedItem(undefined);
                     }}
                     documentId={editedItem?.["@id"]}
                     utilisateurId={props.utilisateurId}
                     setEditedItem={setEditedItem}
                  />
               )}
               <Button type="primary" icon={<PlusOutlined />} onClick={() => setEditedItem({})}>
                  Ajouter un document
               </Button>
            </div>
         </Flex>

         <DocumentList documents={documents?.items || []} utilisateurId={props.utilisateurId} />
      </>
   );
}
