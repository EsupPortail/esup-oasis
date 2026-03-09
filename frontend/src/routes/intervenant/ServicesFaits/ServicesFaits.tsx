/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useEffect, useState } from "react";
import { Alert, Button, Card, Layout, Modal, Space, Table, Tabs, Typography } from "antd";
import { useApi } from "../../../context/api/ApiProvider";
import { NB_MAX_ITEMS_PER_PAGE } from "../../../constants";
import { useAuth } from "../../../auth/AuthProvider";
import PeriodeRhItem from "../../../controls/Items/PeriodeRhItem";
import { EyeOutlined } from "@ant-design/icons";
import { IPeriode, IServicesFaits, IServicesFaitsLigne } from "../../../api/ApiTypeHelpers";
import { ServicesFaitsDetailsTable } from "../../../controls/Table/ServicesFaitsDetailsTable";
import { isEnCoursSurPeriode } from "../../../utils/dates";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { useSearchParams } from "react-router-dom";
import { to2Digits } from "../../../utils/number";
import { EvenementsEnCoursTable } from "../../../controls/ServicesFaits/EvenementsEnCoursTable";
import { InterventionsForfaitEnCoursTable } from "../../../controls/ServicesFaits/InterventionsForfaitEnCoursTable";
import { TimezoneAlert } from "../../../controls/Calendar/TimezoneAlert";

/**
 * Renders a table displaying services made by an intervenant.
 *
 * @return ReactElement
 */
export function ServicesFaitsIntervenantTable(): ReactElement {
   const user = useAuth().user;
   const [servicesFaits, setServicesFaits] = useState<IServicesFaits>();
   const [page, setPage] = useState(1);
   const { data: servicesFaitsIntervenant, isLoading } = useApi().useGetCollectionPaginated({
      path: "/intervenants/{uid}/services_faits",
      page: page,
      itemsPerPage: 12,
      parameters: {
         uid: `/intervenants/${user?.uid}` as string,
      },
      query: {
         "order[debut]": "desc",
      },
      enabled: !!user?.uid,
   });

   return (
      <>
         <Table
            dataSource={servicesFaitsIntervenant?.items}
            loading={isLoading}
            rowKey={(record) => record["@id"] as string}
            pagination={{
               total: servicesFaitsIntervenant?.totalItems,
               pageSize: servicesFaitsIntervenant?.itemsPerPage,
               current: page,
               showTotal: (total, range) => (
                  <div className="text-legende mr-1">
                     {range[0]} à {range[1]} / {total}
                  </div>
               ),
            }}
            onChange={(pagination) => {
               if (pagination.current) {
                  setPage(pagination.current);
               }
            }}
            columns={[
               {
                  title: "Période",
                  dataIndex: "periode",
                  key: "periode",
                  render: (periode: IPeriode) => {
                     return <PeriodeRhItem periode={periode} />;
                  },
               },
               {
                  title: "Montant",
                  dataIndex: "lignes",
                  key: "lignes",
                  className: "text-right",
                  render: (lignes) => {
                     // Somme des montants des lignes
                     return (
                        <Space>
                           {to2Digits(
                              lignes.reduce((acc: number, ligne: IServicesFaitsLigne) => {
                                 return (
                                    acc +
                                    parseFloat(ligne.nbHeures || "0") *
                                       parseFloat(ligne.tauxHoraire?.montant || "0")
                                 );
                              }, 0),
                           )}
                           €
                        </Space>
                     );
                  },
               },
               {
                  key: "actions",
                  className: "text-right",
                  width: 115,
                  render: (_value, record) => (
                     <Space>
                        <Button icon={<EyeOutlined />} onClick={() => setServicesFaits(record)}>
                           Consulter le détail
                        </Button>
                     </Space>
                  ),
               },
            ]}
         />
         {servicesFaits && (
            <Modal
               open
               centered
               cancelButtonProps={{
                  className: "d-none",
               }}
               okText="Fermer"
               onCancel={() => setServicesFaits(undefined)}
               onOk={() => setServicesFaits(undefined)}
               width="80%"
               title="Résumé des services faits de la période"
            >
               <ServicesFaitsDetailsTable
                  servicesFaits={servicesFaits}
                  afficherIntervenant={false}
                  afficherExporter={false}
               />
            </Modal>
         )}
      </>
   );
}

/**
 * Component that renders the "Services faits" page for intervenants.
 *
 * @returns {ReactElement} The rendered "Services faits" section.
 */
export function ServicesFaits(): ReactElement {
   const [periodeEnCours, setPeriodeEnCours] = useState<IPeriode>();
   const screens = useBreakpoint();
   const { data: periodes } = useApi().useGetCollectionPaginated({
      path: "/periodes",
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
      query: {
         "order[debut]": "desc",
      },
   });
   const [searchParams] = useSearchParams();
   const [defaultTab] = useState(
      searchParams.get("historique") === "1" ? "historique" : "en-cours",
   );

   useEffect(() => {
      if (periodes) {
         periodes.items.forEach((p) => {
            if (isEnCoursSurPeriode(p.debut, p.fin)) setPeriodeEnCours(p);
         });
      }
   }, [periodes]);

   return (
      <Layout.Content style={{ padding: "0 50px" }}>
         <Typography.Title level={1}>Services faits</Typography.Title>
         <Card>
            <TimezoneAlert />
            <Tabs
               defaultActiveKey={defaultTab}
               tabPosition={screens.lg ? "left" : undefined}
               items={[
                  {
                     key: "en-cours",
                     label: "En cours",
                     children: (
                        <>
                           <Typography.Title level={2} className="mt-1">
                              Période en cours
                           </Typography.Title>
                           <Alert
                              type="info"
                              showIcon
                              className="mb-2"
                              message="Services sur la période en cours"
                              description={
                                 <>
                                    Les services ci-dessous sont ceux de la période{" "}
                                    {periodeEnCours ? (
                                       <>
                                          du{" "}
                                          <PeriodeRhItem
                                             periode={periodeEnCours}
                                             showIcon={false}
                                             showTooltip={false}
                                             className="bg-white"
                                          />
                                       </>
                                    ) : (
                                       "en cours"
                                    )}
                                    . Les heures de cette période sont payées à la fin du mois
                                    prochain à condition que le contrat soit signé lors de la
                                    déclaration des heures en fin de période.
                                    <br />
                                    Si vous constatez une erreur et que vous souhaitez apporter une
                                    modification, merci de prendre contact avec la personne qui vous
                                    a recruté•e pour cet évènement.
                                 </>
                              }
                           />
                           <h3>Évènements</h3>
                           <EvenementsEnCoursTable />
                           <h3>Interventions au forfait</h3>
                           <InterventionsForfaitEnCoursTable />
                        </>
                     ),
                  },
                  {
                     key: "historique",
                     label: "Historique",
                     children: (
                        <>
                           <Typography.Title level={2} className="mt-1">
                              Historique
                           </Typography.Title>
                           <Alert
                              type="info"
                              showIcon
                              className="mb-2"
                              message="Historique des services faits"
                              description={
                                 <>
                                    Les services faits ci-dessous ont été envoyés à la RH.
                                    <br />
                                    <b>Important :</b> les montants indiqués sont des montants
                                    bruts. Ils sont donnés à titre indicatif et ne tiennent pas
                                    compte des éventuels ajustements qui pourraient être apportés
                                    par le service RH.
                                    <br />
                                    Si le contrat n'est pas signé au moment de la déclaration de ces
                                    heures aux RH, le paiement est décalé au mois suivant jusqu'à
                                    signature.
                                 </>
                              }
                           />
                           <ServicesFaitsIntervenantTable />
                        </>
                     ),
                  },
               ]}
            />
         </Card>
      </Layout.Content>
   );
}

export default ServicesFaits;
