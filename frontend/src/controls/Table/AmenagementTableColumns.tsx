/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IAmenagement, ICategorieAmenagement, ITypeAmenagement } from "../../api/ApiTypeHelpers";
import { getDomaineAmenagement } from "../../lib/amenagements";
import React from "react";
import { Badge, Button, Flex, Space, Tag, Tooltip } from "antd";
import { getLibellePeriode } from "../../utils/dates";
import SuiviAmenagementItem from "../Items/SuiviAmenagementItem";
import { NavigateFunction } from "react-router-dom";
import Icon, { EditOutlined, EyeOutlined } from "@ant-design/icons";
import { InscriptionItem } from "../Items/InscriptionItem";
import { ChargesAccompagnementsItem } from "../Items/ChargesAccompagnementsItem";
import { ListeUtilisateurTag } from "../Tags/ListeUtilisateurTag";
import { ReactComponent as ExternalLink } from "../../assets/images/external-link.svg";
import { EllipsisParagraph } from "../Typography/EllipsisParagraph";
import EtudiantItem from "../Items/EtudiantItem";
import { RoleValues } from "../../lib/Utilisateur";
import { ascToAscend } from "../../utils/array";
import { FilterProps } from "../../utils/table";
import { FiltreAmenagement } from "./AmenagementTableLayout";
import { ColumnsType } from "antd/lib/table";
import { ColumnType } from "antd/es/table";

export function amenagementTableColumns(props: {
   filtre: FiltreAmenagement;
   setFiltre: React.Dispatch<React.SetStateAction<FiltreAmenagement>>;
   typesAmenagements?: ITypeAmenagement[];
   categoriesAmenagements?: ICategorieAmenagement[];
   navigate: NavigateFunction;
   setEditedItem?: (id: string) => void;
   isGestionnaire?: boolean;
}): ColumnsType<IAmenagement> {
   return [
      {
         title: "Bénéficiaire",
         dataIndex: "beneficiaire",
         fixed: "left",
         sorter: true,
         sortOrder: ascToAscend(props.filtre?.["order[beneficiaires.utilisateur.nom]"]),
         ...FilterProps<FiltreAmenagement>("nom", props.filtre, props.setFiltre),
         filteredValue: props.filtre?.nom ? [props.filtre?.nom] : null,
         onCell: (record: IAmenagement) => {
            return {
               className: props.isGestionnaire ? "pointer" : undefined,
               onClick: () => {
                  if (!props.isGestionnaire) return;
                  props.navigate(
                     (record.beneficiaire?.["@id"] as string).replace("/utilisateurs/", "/beneficiaires/"),
                  );
               },
            };
         },
         render: (_value: string, record: IAmenagement) => {
            return (
               <Flex justify="space-between" align="center">
                  <EtudiantItem
                     utilisateurId={record.beneficiaire?.["@id"]}
                     responsive="lg"
                     role={RoleValues.ROLE_BENEFICIAIRE}
                     highlight={props.filtre.nom}
                  />
                  {props.isGestionnaire && (
                     <Tooltip title="Ouvrir dans un nouvel onglet">
                        <Button
                           size="small"
                           type="text"
                           className="text-light"
                           icon={<Icon component={ExternalLink} className="fs-08" />}
                           onClick={(e) => {
                              e.stopPropagation();
                              window.open(
                                 record.beneficiaire?.["@id"]?.replace("/utilisateurs/", "/beneficiaires/"),
                                 "_blank",
                              );
                           }}
                        />
                     </Tooltip>
                  )}
               </Flex>
            );
         },
      } as ColumnType<IAmenagement>,
      {
         title: "Inscription",
         dataIndex: "inscription",
         render: (_value: string, record: IAmenagement) => {
            return <InscriptionItem utilisateurId={record.beneficiaire?.["@id"]} />;
         },
      },
      {
         title: "Domaine",
         dataIndex: "domaine",
         render: (_value: string, record: IAmenagement) => {
            return (
               <div style={{ width: 140 }}>
                  <Badge
                     className="mr-1"
                     color={
                        getDomaineAmenagement(
                           props.typesAmenagements?.find(
                              (ta) => ta["@id"] === record.typeAmenagement,
                           ),
                        )?.couleur || "default"
                     }
                  />
                  {getDomaineAmenagement(
                     props.typesAmenagements?.find((ta) => ta["@id"] === record.typeAmenagement),
                  )?.singulier || "Non renseigné"}
               </div>
            );
         },
      },
      {
         title: "Type aménagement",
         dataIndex: "typeAmenagement",
         render: (_value: string, record: IAmenagement) => {
            const typeAmenagement = props.typesAmenagements?.find(
               (ta) => ta["@id"] === record.typeAmenagement,
            );

            return (
               <Space size={0} direction="vertical" style={{ width: 225 }}>
                  <span className="light text-legende fs-09">
                     {
                        props.categoriesAmenagements?.find(
                           (c) => c["@id"] === typeAmenagement?.categorie,
                        )?.libelle
                     }
                  </span>
                  <span className="semi-bold">
                     {
                        props.typesAmenagements?.find((ta) => ta["@id"] === record.typeAmenagement)
                           ?.libelle
                     }
                  </span>
               </Space>
            );
         },
      },

      {
         title: <div style={{ width: 200 }}>Commentaire</div>,
         dataIndex: "commentaire",
         render: (value: string) => {
            return value ? (
               <EllipsisParagraph content={value} className="text-legende fs-09 mt-1 mb-0 light" />
            ) : undefined;
         },
      },

      {
         title: "Période",
         dataIndex: "periode",
         render: (_value: string, record: IAmenagement) => {
            return (
               <Space direction="vertical" size={4}>
                  <Space size={4}>
                     {record.semestre1 && (
                        <Tooltip title="Semestre 1">
                           <Tag key="s1" className="mr-0">
                              S1
                           </Tag>
                        </Tooltip>
                     )}
                     {record.semestre2 && (
                        <Tooltip title="Semestre 2">
                           <Tag key="s2" className="mr-0">
                              S2
                           </Tag>
                        </Tooltip>
                     )}
                  </Space>
                  {(record.debut || record.fin) && (
                     <Tag key="periode">{getLibellePeriode(record.debut, record.fin, "MMM")}</Tag>
                  )}
               </Space>
            );
         },
      },

      {
         title: <div style={{ width: 200 }}>Tags</div>,
         dataIndex: "tags",
         render: (_value: string, record: IAmenagement) => {
            return <ListeUtilisateurTag utilisateurId={record.beneficiaire?.["@id"] as string} />;
         },
      },

      {
         title: "Suivi",
         dataIndex: "suivi",
         render: (value: string) => {
            return value ? <SuiviAmenagementItem suiviId={value} /> : undefined;
         },
      },

      {
         title: <span aria-label="Chargés d'accompagnement">Chargé•es d'acc.</span>,
         dataIndex: "cas",
         render: (_value: string, record: IAmenagement) => {
            return <ChargesAccompagnementsItem utilisateurId={record.beneficiaire?.["@id"]} />;
         },
      },
      {
         title: "",
         dataIndex: "actions",
         className: "text-right",
         render: (_value: string, record: IAmenagement) => {
            if (props.setEditedItem) {
               // Pour les renforts
               return (
                  <Button
                     className="mt-2"
                     icon={<EditOutlined />}
                     onClick={() => props.setEditedItem?.(record["@id"] as string)}
                  >
                     Éditer
                  </Button>
               );
            }

            // Pour les gestionnaires
            return (
               <Button.Group>
                  <Button
                     icon={<EyeOutlined />}
                     onClick={() =>
                        props.navigate(
                           `${(record.beneficiaire?.["@id"])?.replace("/utilisateurs/", "/beneficiaires/")}?amenagement=${record["@id"]}&domaine=${
                              getDomaineAmenagement(
                                 props.typesAmenagements?.find(
                                    (ta) => ta["@id"] === record.typeAmenagement,
                                 ),
                              )?.id
                           }`,
                        )
                     }
                  >
                     Voir
                  </Button>
                  <Tooltip title="Ouvrir dans un nouvel onglet">
                     <Button
                        className="text-light"
                        icon={<Icon component={ExternalLink} className="fs-08" />}
                        onClick={() => {
                           window.open(
                              `${(record.beneficiaire?.["@id"])?.replace("/utilisateurs/", "/beneficiaires/")}?amenagement=${record["@id"]}&domaine=${
                                 getDomaineAmenagement(
                                    props.typesAmenagements?.find(
                                       (ta) => ta["@id"] === record.typeAmenagement,
                                    ),
                                 )?.id
                              }`,
                              "_blank",
                           );
                        }}
                     />
                  </Tooltip>
               </Button.Group>
            );
         },
      },
   ];
}
