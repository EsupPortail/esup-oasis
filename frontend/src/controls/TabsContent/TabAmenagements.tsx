/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import {
   AmenagementDomaine,
   DomaineAmenagementInfos,
   getAmenagementsByCategories,
} from "../../lib/amenagements";
import React, { useMemo } from "react";
import { IAmenagement } from "../../api/ApiTypeHelpers";
import { useApi } from "../../context/api/ApiProvider";
import {
   PREFETCH_CATEGORIES_AMENAGEMENTS,
   PREFETCH_TYPES_AMENAGEMENTS,
} from "../../api/ApiPrefetchHelpers";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { Avatar, Button, Card, Col, Empty, Flex, Row, Space, Tag, Tooltip, Typography } from "antd";
import {
   ArrowRightOutlined,
   CalendarOutlined,
   CommentOutlined,
   EditOutlined,
   HarmonyOSOutlined,
} from "@ant-design/icons";
import { ModalAmenagement } from "../Modals/ModalAmenagement";
import { getLibellePeriode } from "../../utils/dates";
import SuiviAmenagementItem from "../Items/SuiviAmenagementItem";
import { useSearchParams } from "react-router-dom";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { EllipsisParagraph } from "../Typography/EllipsisParagraph";
import { ButtonAddAmenagement } from "./ButtonAddAmenagement";
import { BoutonDecisionEtab } from "./BoutonDecisionEtab";

export function TabAmenagements(props: {
   utilisateurId: string;
   domaineAmenagement: DomaineAmenagementInfos;
}) {
   const screens = useBreakpoint();
   const [searchParams] = useSearchParams();
   const [editedAmenagement, setEditedAmenagement] = React.useState<IAmenagement>();
   const { data: typesAmenagements } = useApi().useGetCollection(PREFETCH_TYPES_AMENAGEMENTS);
   const { data: categoriesAmenagements } = useApi().useGetCollection(
      PREFETCH_CATEGORIES_AMENAGEMENTS,
   );
   const { data: amenagements } = useApi().useGetCollectionPaginated({
      path: "/utilisateurs/{uid}/amenagements",
      parameters: {
         uid: props.utilisateurId,
      },
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
   });

   const amenagementsByCategories = useMemo(() => {
      return getAmenagementsByCategories(
         amenagements?.items || [],
         categoriesAmenagements?.items || [],
         typesAmenagements?.items || [],
         props.domaineAmenagement.id,
      );
   }, [
      amenagements?.items,
      categoriesAmenagements?.items,
      props.domaineAmenagement.id,
      typesAmenagements?.items,
   ]);

   const highlightAmenagement = searchParams.get("amenagement");

   return (
      <>
         <Flex justify="space-between" align="center" className="mt-1 mb-2" wrap>
            <Typography.Title level={3} className="mt-0 mb-0">
               <Avatar size="small" className={`mr-2 bg-${props.domaineAmenagement.couleur}`} />
               {props.domaineAmenagement.libelleLongPluriel}
            </Typography.Title>
            <div className={`text-right${!screens.lg ? " mt-2" : ""}`}>
               {props.domaineAmenagement.id === AmenagementDomaine.examen && (
                  <BoutonDecisionEtab utilisateurId={props.utilisateurId} />
               )}
               <ButtonAddAmenagement
                  utilisateurId={props.utilisateurId}
                  domaineAmenagement={props.domaineAmenagement}
               />
            </div>
         </Flex>

         <div>
            <ModalAmenagement
               open={!!editedAmenagement}
               setOpen={() => setEditedAmenagement(undefined)}
               amenagementId={editedAmenagement?.["@id"]}
               utilisateurId={props.utilisateurId}
               domaineAmenagement={props.domaineAmenagement}
            />

            <div>
               <Row gutter={[16, 16]}>
                  {amenagementsByCategories?.map((c) => (
                     <span
                        key={c["@id"]}
                        style={{ width: "100%", display: "contents" }}
                        className={`border-color-${props.domaineAmenagement.couleur}`}
                     >
                        {c.typeAmenagements.map((ta) => (
                           <span key={ta["@id"]} style={{ width: "100%", display: "contents" }}>
                              {ta.amenagements.map((a) => (
                                 <Col key={a["@id"]} xs={24} sm={24} md={24} lg={12} xl={8} xxl={6}>
                                    <Card
                                       style={{ borderColor: "#e0e0e0" }}
                                       styles={
                                          screens.lg ? { body: { minHeight: 195 } } : undefined
                                       }
                                       className={`${
                                          highlightAmenagement === a["@id"]
                                             ? "highlightAmenagement"
                                             : ""
                                       } bg-${props.domaineAmenagement.couleur}-xxlight`}
                                    >
                                       <Card.Meta
                                          title={
                                             <Flex
                                                wrap="wrap"
                                                className="w-100"
                                                justify="space-between"
                                                align="start"
                                             >
                                                <div>
                                                   <h5 className="mt-0 mb-0 fs-11">{ta.libelle}</h5>
                                                   <span
                                                      className={`text-${props.domaineAmenagement.couleur}-dark fs-09`}
                                                   >
                                                      <ArrowRightOutlined className="mr-1" />
                                                      {c.libelle}
                                                   </span>
                                                </div>
                                                <Button
                                                   className="mr-0 pr-0"
                                                   type="text"
                                                   icon={<EditOutlined aria-label="Menu" />}
                                                   onClick={() => setEditedAmenagement(a)}
                                                />
                                             </Flex>
                                          }
                                          description={
                                             <Space direction="vertical" className="text-text">
                                                {a.debut || a.fin ? (
                                                   <Space align="start" size={[8, 2]} wrap>
                                                      <CalendarOutlined />
                                                      <Tag>
                                                         {getLibellePeriode(a.debut, a.fin, "MMM")}
                                                      </Tag>
                                                      <Space size={0}>
                                                         {a.semestre1 && (
                                                            <Tooltip title="Semestre 1">
                                                               <Tag>S1</Tag>
                                                            </Tooltip>
                                                         )}
                                                         {a.semestre2 && (
                                                            <Tooltip title="Semestre 2">
                                                               <Tag>S2</Tag>
                                                            </Tooltip>
                                                         )}
                                                      </Space>
                                                   </Space>
                                                ) : null}
                                                {a.commentaire && a.commentaire.length > 0 && (
                                                   <Space align="start" size={12}>
                                                      <CommentOutlined />
                                                      <EllipsisParagraph
                                                         content={a.commentaire}
                                                         className="light mb-0 fs-09"
                                                         type="secondary"
                                                      />
                                                   </Space>
                                                )}
                                                {a.suivi ? (
                                                   <Space align="start" size={12}>
                                                      <HarmonyOSOutlined />
                                                      <SuiviAmenagementItem
                                                         suiviId={a.suivi}
                                                         className="float-right"
                                                         couleur={props.domaineAmenagement.couleur}
                                                      />
                                                   </Space>
                                                ) : null}
                                             </Space>
                                          }
                                       />
                                    </Card>
                                 </Col>
                              ))}
                           </span>
                        ))}
                     </span>
                  ))}
               </Row>
            </div>
            {amenagementsByCategories?.length === 0 && (
               <Empty className="mt-3 mb-1" description="Aucun aménagement" />
            )}
         </div>
      </>
   );
}
