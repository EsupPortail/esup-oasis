/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Breadcrumb, Button, Flex, Layout, Skeleton, Space, Typography } from "antd";
import { NavLink, useParams } from "react-router";
import { DossierDemande as Dossier } from "@controls/Demande/Dossier/DossierDemande";
import { HomeFilled, QuestionCircleFilled } from "@ant-design/icons";
import { useApi } from "@context/api/ApiProvider";
import Spinner from "@controls/Spinner/Spinner";
import { QuestionnaireProvider } from "@context/demande/QuestionnaireProvider";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { DemandeurTour } from "@controls/Demande/Tour/DemandeurTour";
import { env } from "@/env";

export type RefsTourDemande = {
  avancement: React.RefObject<HTMLDivElement | null>;
  dossier: React.RefObject<HTMLDivElement | null>;
};

/**
 * Renders the page for ROLE_GESTIONNAIRE to manage demande.
 *
 * @returns {ReactElement} The rendered Beneficiaires component.
 */
export default function Demande(): ReactElement {
  const { id } = useParams<"id">();
  const screens = useBreakpoint();
  const { data: demande } = useApi().useGetItem({
    path: "/demandes/{id}",
    url: `/demandes/${id}`,
    enabled: !!id,
  });
  const { data: typeDemande } = useApi().useGetItem({
    path: "/types_demandes/{id}",
    url: demande?.typeDemande as string,
    enabled: !!demande?.typeDemande,
  });

  const [afficherTour, setAfficherTour] = React.useState<boolean>(false);
  const refs: RefsTourDemande = {
    avancement: React.useRef<HTMLDivElement>(null),
    dossier: React.useRef<HTMLDivElement>(null),
  };

  return (
    <Layout.Content className="demandes" style={{ padding: "0 50px" }}>
      {afficherTour && <DemandeurTour open={afficherTour} setOpen={setAfficherTour} refs={refs} />}
      <Flex justify="space-between" align="center">
        <Typography.Title level={1}>Informations sur la demande</Typography.Title>
        {env.REACT_APP_VISITE_GUIDEE !== "false" && screens.lg && (
          <Button
            icon={<QuestionCircleFilled />}
            className="mb-0"
            onClick={() => setAfficherTour(true)}
          >
            Visite guidée
          </Button>
        )}
      </Flex>

      <Breadcrumb
        className="mt-2"
        items={[
          {
            key: "demandes",
            title: (
              <NavLink to="/demandeurs">
                <Space>
                  <HomeFilled />
                  Demandeurs
                </Space>
              </NavLink>
            ),
          },
          {
            key: "demande",
            title: demande ? (
              <>
                {demande.demandeur?.prenom} {demande.demandeur?.nom?.toLocaleUpperCase()}
                {typeDemande && (
                  <Typography.Text type="secondary" className="ml-1">
                    ({typeDemande.libelle})
                  </Typography.Text>
                )}
              </>
            ) : (
              <Spinner />
            ),
          },
        ]}
      />
      {demande ? (
        <QuestionnaireProvider demandeId={demande["@id"]} mode="preview">
          <Dossier refs={refs} affichageTour={afficherTour} />
        </QuestionnaireProvider>
      ) : (
        <Skeleton paragraph={{ rows: 4 }} active />
      )}
    </Layout.Content>
  );
}
