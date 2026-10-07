/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Alert, Button, Row } from "antd";
import "@routes/gestionnaire/dashboard/Dashboard.scss";
import { useNavigate } from "react-router";
import { useApi } from "@context/api/ApiProvider";
import { PREFETCH_ETAT_DEMANDE, PREFETCH_TYPES_EVENEMENTS } from "@api";
import { EyeOutlined, WarningFilled } from "@ant-design/icons";
import { useAuth } from "@/auth/AuthProvider";
import ActiviteServiceStats from "@controls/Dashboard/ActiviteServiceStats";
import DemandesAccompagnementStats from "@controls/Dashboard/DemandesAccompagnementStats";
import PlanificationEvenementsStats from "@controls/Dashboard/PlanificationEvenementsStats";
import { env } from "@/env";

interface IDashboardUtilisateurProps {
  utilisateurId: string;
}

/**
 * Render a user dashboard with stats
 * @param {object} props - The props object
 * @param {number} props.utilisateurId - The user ID
 * @returns {ReactElement} - The rendered dashboard component
 */
export default function DashboardUtilisateurStats({
  utilisateurId,
}: IDashboardUtilisateurProps): ReactElement {
  const user = useAuth().user;
  const navigate = useNavigate();
  const { data: typesEvenements } = useApi().useGetFullCollection(PREFETCH_TYPES_EVENEMENTS);
  const { data: etatsDemande } = useApi().useGetFullCollection(PREFETCH_ETAT_DEMANDE);
  const { data: stats, isFetching } = useApi().useGetItem({
    path: "/statistiques",
    url: "/statistiques",
    query: {
      utilisateur: utilisateurId,
    },
    enabled: !!utilisateurId,
    onError: () => {},
  });

  return (
    <>
      {user?.isGestionnaire && (stats?.evenementsSansBeneficiaire || 0) > 0 && (
        <Alert
          type="error"
          showIcon
          icon={<WarningFilled />}
          title="Évènement sans bénéficiaire détecté !"
          description={`Corrigez cette situation avant l'envoi de la période à la RH.`}
          action={
            <Button
              ghost
              danger
              icon={<EyeOutlined />}
              onClick={() => navigate("/planning/evenements-sans-beneficiaires")}
            >
              Consulter les évènements
            </Button>
          }
        />
      )}
      <Row>
        <ActiviteServiceStats stats={stats} isFetching={isFetching} />
        {env.REACT_APP_GERER_DEMANDES && (
          <DemandesAccompagnementStats
            stats={stats}
            etatsDemande={etatsDemande?.items}
            isFetching={isFetching}
          />
        )}
        <PlanificationEvenementsStats
          stats={stats}
          typesEvenements={typesEvenements?.items}
          isFetching={isFetching}
        />
      </Row>
    </>
  );
}
