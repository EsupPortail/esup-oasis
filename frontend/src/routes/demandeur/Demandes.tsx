/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useState } from "react";
import { Alert, Button, Card, Layout, Typography } from "antd";
import CampagneDemandeList from "@controls/List/CampagneDemandeList";
import { useApi } from "@context/api/ApiProvider";
import { InfoCircleOutlined, LinkOutlined, PlusOutlined } from "@ant-design/icons";
import NouvelleDemandeModale from "@controls/Modals/Demande/NouvelleDemandeModale";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { useAuth } from "@/auth/AuthProvider";
import { env } from "@/env";
import { service } from "@lib";

/**
 * Page de gestion des demandes (demandeur)
 * @constructor
 */
export default function Demandes() {
  const screens = useBreakpoint();
  const user = useAuth().user;
  const [modaleNouvelleDemande, setModaleNouvelleDemande] = useState<boolean>(false);
  const { data: demandes, isFetching: isFetchingDemandes } = useApi().useGetFullCollection({
    path: "/utilisateurs/{uid}/demandes",
    query: { format_simple: true },
    enabled: !!user,
    parameters: { uid: user?.["@id"] as string },
  });

  return (
    <Layout.Content style={{ padding: "0 50px" }}>
      <Typography.Title level={1}>Demandes</Typography.Title>
      <div>
        <Alert
          type="info"
          showIcon={screens.lg}
          icon={<InfoCircleOutlined aria-hidden />}
          title={`L'accompagnement ${service.sigle}`}
          description={
            <>
              <p className="semi-bold">
                {service.DefiniLong} accompagne les étudiants et étudiantes qui souhaitent
                bénéficier d’aménagements d’études du fait d’un statut spécifique.
              </p>
              <p>
                L’accompagnement proposé a pour objectif la réalisation du projet d’étude malgré des
                contraintes personnelles et/ou une activité extra-universitaire intenses. Les
                aménagements proposés sont personnalisés et évolutifs.
              </p>
              <p>
                La demande de rattachement doit être actualisée{" "}
                <b className="semi-bold">à chaque rentrée</b>.
              </p>
              {env.REACT_APP_URL_SERVICE && (
                <div className="mt-2">
                  <Typography.Link
                    className="text-text"
                    style={{ textDecoration: "underline" }}
                    target="_blank"
                    href={env.REACT_APP_URL_SERVICE}
                  >
                    <LinkOutlined className="mr-1" aria-hidden style={{ verticalAlign: "sub" }} />A
                    propos {service.de} et des accompagnements proposés
                  </Typography.Link>
                </div>
              )}
            </>
          }
        />
      </div>
      <Card
        className="mt-3"
        title={screens.md && <h2>Vos demandes</h2>}
        extra={[
          <Button
            key="add"
            type="primary"
            icon={<PlusOutlined aria-hidden />}
            onClick={() => setModaleNouvelleDemande(true)}
          >
            Déposer une nouvelle demande
          </Button>,
        ]}
      >
        <h3 className="mt-0">Vos demandes en cours</h3>
        <CampagneDemandeList isFetching={isFetchingDemandes} demandes={demandes?.items} />
      </Card>
      <NouvelleDemandeModale open={modaleNouvelleDemande} setOpen={setModaleNouvelleDemande} />
    </Layout.Content>
  );
}
