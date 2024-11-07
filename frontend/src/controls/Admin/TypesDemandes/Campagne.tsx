/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useState } from "react";
import { useApi } from "../../../context/api/ApiProvider";
import Spinner from "../../Spinner/Spinner";
import { Alert, Badge, Button, Descriptions, DescriptionsProps, Flex, Space, Tooltip } from "antd";
import dayjs from "dayjs";
import Icon, { EditOutlined, EyeOutlined, MinusOutlined, PlusOutlined } from "@ant-design/icons";
import { isEnCoursSurPeriode } from "../../../utils/dates";
import { CampagneEdition } from "./CampagneEdition";
import { ICampagneDemande } from "../../../api/ApiTypeHelpers";
import QuestionnaireModale from "./QuestionnaireModale";
import { useNavigate } from "react-router-dom";
import { ReactComponent as ExternalLink } from "../../../assets/images/external-link.svg";
import { entiteParent } from "../../../api/Utils";

export function Campagne(props: {
   title: string;
   typeDemandeId: string;
   campagneId: string | null | undefined;
   showError?: boolean;
}): React.ReactElement {
   const navigate = useNavigate();
   const [typeDemandeApercu, setTypeDemandeApercu] = useState<string>();
   const [editedItem, setEditedItem] = useState<ICampagneDemande>();
   const { data: campagne, isFetching } = useApi().useGetItem({
      path: "/types_demandes/{typeId}/campagnes/{id}",
      url: props.campagneId as string,
      enabled: !!props.campagneId,
   });

   const { data: commission, isFetching: isFetchingCommission } = useApi().useGetItem({
      path: "/commissions/{id}",
      url: campagne?.commission as string,
      enabled: !!campagne?.commission,
   });

   if (isFetching || isFetchingCommission) {
      return <Spinner />;
   }

   const items: DescriptionsProps["items"] = campagne
      ? [
           {
              key: "libelle",
              label: "Campagne",
              children: campagne.libelle,
              span: 3,
           },
           {
              key: "debut",
              label: "Début",
              children: campagne.debut ? (
                 dayjs(campagne.debut).format("DD/MM/YYYY")
              ) : (
                 <MinusOutlined />
              ),
           },
           {
              key: "fin",
              label: "Fin",
              children: campagne.fin ? dayjs(campagne.fin).format("DD/MM/YYYY") : <MinusOutlined />,
           },
           {
              key: "statut",
              label: "Statut",
              children: isEnCoursSurPeriode(campagne.debut, campagne.fin) ? (
                 <Badge status="success" text="Ouverte" />
              ) : (
                 <Badge status="default" text="Fermée" />
              ),
           },
           {
              key: "commission",
              label: "Commission",
              children: (
                 <Flex justify="space-between" align="center">
                    <div>{commission ? commission.libelle : <MinusOutlined />}</div>
                    <div>
                       {commission && (
                          <Button.Group>
                             <Button
                                icon={<EyeOutlined />}
                                onClick={() => {
                                   navigate(`/administration/referentiels${commission?.["@id"]}`);
                                }}
                             >
                                Voir
                             </Button>
                             <Tooltip title="Ouvrir dans un nouvel onglet">
                                <Button
                                   className="text-light"
                                   icon={<Icon component={ExternalLink} className="fs-08" />}
                                   onClick={() => {
                                      window.open(
                                         `/administration/referentiels${commission?.["@id"]}`,
                                         "_blank",
                                      );
                                   }}
                                />
                             </Tooltip>
                          </Button.Group>
                       )}
                    </div>
                 </Flex>
              ),
              span: 2,
           },
           {
              key: "dateCommission",
              label: "Date de commission",
              children: campagne.dateCommission ? (
                 dayjs(campagne.dateCommission).format("DD/MM/YYYY")
              ) : (
                 <MinusOutlined />
              ),
              span: 1,
           },
           {
              key: "questionnaire",
              label: "Questionnaire",
              children: (
                 <Button
                    onClick={() => setTypeDemandeApercu(entiteParent(campagne?.["@id"]))}
                    icon={<EyeOutlined />}
                 >
                    Consulter le questionnaire associé
                 </Button>
              ),
              span: 2,
           },
           {
              key: "dateArchivage",
              label: "Date d'archivage",
              children: campagne.dateArchivage ? (
                 dayjs(campagne.dateArchivage).format("DD/MM/YYYY")
              ) : (
                 <MinusOutlined />
              ),
              span: 1,
           },
        ]
      : [];

   return (
      <>
         <CampagneEdition
            setEditedItem={setEditedItem}
            typeDemandeId={props.typeDemandeId}
            editedItem={editedItem}
         />
         {campagne ? (
            <>
               <QuestionnaireModale
                  typeDemandeId={typeDemandeApercu}
                  setTypeDemandeId={setTypeDemandeApercu}
               />
               <Descriptions
                  extra={
                     <Button icon={<EditOutlined />} onClick={() => setEditedItem(campagne)}>
                        Editer la campagne
                     </Button>
                  }
                  title={
                     <Space>
                        <MinusOutlined />
                        {props.title}
                     </Space>
                  }
                  bordered
                  items={items}
                  className="w-100 mt-2 mb-2"
                  column={3}
               />
            </>
         ) : (
            <Alert
               className="w-100"
               message={props.title}
               description="Aucune campagne déclarée."
               type={props.showError ? "error" : "info"}
               showIcon
               action={
                  <Button type="primary" icon={<PlusOutlined />} onClick={() => setEditedItem({})}>
                     Ajouter une campagne
                  </Button>
               }
            />
         )}
      </>
   );
}
