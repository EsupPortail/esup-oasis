/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useApi } from "../../context/api/ApiProvider";
import { Skeleton, Space, Tabs, Typography } from "antd";
import React from "react";
import "./DossierBeneficiaire.scss";
import {
   AppstoreAddOutlined,
   CalendarOutlined,
   FileDoneOutlined,
   FileTextOutlined,
   MedicineBoxOutlined,
   UserOutlined,
} from "@ant-design/icons";
import { TabIdentite } from "../TabsContent/TabIdentite";
import { TabDemandes } from "../TabsContent/TabDemandes";
import { DOMAINES_AMENAGEMENTS_INFOS } from "../../lib/amenagements";
import { TabAmenagements } from "../TabsContent/TabAmenagements";
import { TabDecisionEtab } from "../TabsContent/TabDecisionEtab";
import { useAuth } from "../../auth/AuthProvider";
import { UtilisateurTags } from "../Tags/UtilisateurTags";
import { useSearchParams } from "react-router-dom";
import { TabAvisEse } from "../TabsContent/TabAvisEse";
import { BeneficiaireAvisEseAvatar } from "../Avatars/BeneficiaireAvisEseAvatar";
import { TabEntretiens } from "../TabsContent/TabEntretiens";
import AmenagementBadge from "../Badge/AmenagementBadge";
import EntretiensBadge from "../Badge/EntretiensBadge";
import DemandesBadge from "../Badge/DemandesBadge";
import { TabDocuments } from "../TabsContent/TabDocuments";
import { env } from "../../env";

export default function DossierBeneficiaire(props: { beneficiaireId: string }): React.ReactElement {
   const user = useAuth().user;
   const [searchParams] = useSearchParams();
   const { data: beneficiaire } = useApi().useGetItem({
      path: "/utilisateurs/{uid}",
      url: props.beneficiaireId,
      enabled: !!props.beneficiaireId,
   });

   if (!beneficiaire) {
      return <Skeleton active paragraph={{ rows: 4 }} />;
   }

   return (
      <>
         <Typography.Title level={2} className="sr-only">
            {beneficiaire?.prenom} {beneficiaire?.nom?.toLocaleUpperCase()}
         </Typography.Title>
         <UtilisateurTags utilisateurId={props.beneficiaireId} />
         <Tabs
            defaultActiveKey={searchParams.get("domaine") ?? "identite"}
            type="card"
            rootClassName="tabs-tab-card"
            items={[
               {
                  key: "identite",
                  label: "Identité",
                  children: <TabIdentite utilisateurId={props.beneficiaireId as string} />,
                  icon: <UserOutlined />,
               },
               {
                  key: "demandes",
                  label: (
                     <Space>
                        Demandes
                        <DemandesBadge utilisateurId={props.beneficiaireId} />
                     </Space>
                  ),
                  children: (
                     <TabDemandes
                        utilisateur={beneficiaire}
                        title={
                           <Typography.Title level={3} className="mt-1">
                              Demandes déposées
                           </Typography.Title>
                        }
                     />
                  ),
                  icon: <FileDoneOutlined />,
               },
               ...Object.values(DOMAINES_AMENAGEMENTS_INFOS)
                  .filter(
                     (typeAmenagement) =>
                        user?.isGestionnaire || (user?.isRenfort && typeAmenagement.visibleRenfort),
                  )
                  .map((domaineAmenagement) => {
                     return {
                        key: domaineAmenagement.id,
                        label: (
                           <Space>
                              {domaineAmenagement.libelleLongPluriel}
                              <AmenagementBadge
                                 utilisateurId={props.beneficiaireId}
                                 domaineAmenagement={domaineAmenagement}
                              />
                           </Space>
                        ),
                        children: (
                           <TabAmenagements
                              utilisateurId={props.beneficiaireId}
                              domaineAmenagement={domaineAmenagement}
                           />
                        ),
                        icon: <AppstoreAddOutlined />,
                     };
                  }),
               {
                  key: "decision",
                  label: (
                     <Space>
                        Décision d'étab
                        <AmenagementBadge
                           utilisateurId={props.beneficiaireId}
                           decision
                        />
                     </Space>
                  ),
                  children: (
                     <TabDecisionEtab
                        utilisateurId={props.beneficiaireId}
                     />
                  ),
                  icon: <FileDoneOutlined />,
               },
               {
                  key: "ese",
                  label: (
                     <Space>
                        <span>Avis {env.REACT_APP_ESPACE_SANTE_ABV || "santé"}</span>
                        <BeneficiaireAvisEseAvatar utilisateurId={props.beneficiaireId as string} />
                     </Space>
                  ),
                  children: <TabAvisEse utilisateurId={props.beneficiaireId as string} />,
                  icon: <MedicineBoxOutlined />,
               },
               {
                  key: "entretiens",
                  label: (
                     <Space>
                        Synthèses d'entretiens
                        <EntretiensBadge utilisateurId={props.beneficiaireId} />
                     </Space>
                  ),
                  children: <TabEntretiens utilisateurId={props.beneficiaireId as string} />,
                  icon: <CalendarOutlined />,
               },
               {
                  key: "documents",
                  label: <Space>Documents</Space>,
                  children: <TabDocuments utilisateurId={props.beneficiaireId as string} />,
                  icon: <FileTextOutlined />,
               },
            ]}
         />
      </>
   );
}
