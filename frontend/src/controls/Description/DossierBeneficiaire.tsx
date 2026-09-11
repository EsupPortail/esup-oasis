/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useApi } from "@context/api/ApiProvider";
import { Skeleton, Space, Tabs, Typography } from "antd";
import React from "react";
import "@controls/Description/DossierBeneficiaire.scss";
import {
  AppstoreAddOutlined,
  CalendarOutlined,
  FileDoneOutlined,
  FileTextOutlined,
  MedicineBoxOutlined,
  UserOutlined,
} from "@ant-design/icons";
import { TabIdentite } from "@controls/TabsContent/TabIdentite";
import { TabDemandes } from "@controls/TabsContent/TabDemandes";
import { DOMAINES_AMENAGEMENTS_INFOS } from "@lib";
import { TabAmenagements } from "@controls/TabsContent/TabAmenagements";
import { TabDecisionEtab } from "@controls/TabsContent/TabDecisionEtab";
import { useAuth } from "@/auth/AuthProvider";
import { UtilisateurTags } from "@controls/Tags/UtilisateurTags";
import { useSearchParams } from "react-router-dom";
import { TabAvisEse } from "@controls/TabsContent/TabAvisEse";
import { BeneficiaireAvisEseAvatar } from "@controls/Avatars/BeneficiaireAvisEseAvatar";
import { TabEntretiens } from "@controls/TabsContent/TabEntretiens";
import AmenagementBadge from "@controls/Badge/AmenagementBadge";
import EntretiensBadge from "@controls/Badge/EntretiensBadge";
import DemandesBadge from "@controls/Badge/DemandesBadge";
import { TabDocuments } from "@controls/TabsContent/TabDocuments";
import { env } from "@/env";

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
          env.REACT_APP_GERER_DEMANDES
            ? {
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
              }
            : null,
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
        ].filter((t) => t !== null)}
      />
    </>
  );
}
