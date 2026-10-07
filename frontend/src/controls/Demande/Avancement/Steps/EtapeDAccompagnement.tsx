/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React from "react";
import { Button, Space, Typography } from "antd";
import { useNavigate } from "react-router";
import { ETAT_ATTENTE_CHARTES, ETAT_DEMANDE_REFUSEE, ETAT_DEMANDE_VALIDEE, EtatInfo } from "@lib";
import { IDemande } from "@api";
import { useApi } from "@context/api/ApiProvider";
import { FONCTIONNALITES, useQuestionnaire } from "@context/demande/QuestionnaireProvider";
import ValidationAccompagnementButton from "@controls/Demande/ValidationAccompagnementButton";

interface EtapeDAccompagnementProps {
  demande: IDemande;
  etatDemande: EtatInfo;
}

export default function EtapeDAccompagnement({ demande, etatDemande }: EtapeDAccompagnementProps) {
  const navigate = useNavigate();
  const { questUtils } = useQuestionnaire();
  const { data: beneficiaire } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: demande.demandeur?.["@id"] as string,
    enabled: demande.demandeur !== undefined,
  });

  if (etatDemande.etape < "D" || etatDemande.id === ETAT_ATTENTE_CHARTES) return null;

  if (etatDemande.id === ETAT_DEMANDE_REFUSEE) {
    return <Typography.Text type="danger">Accompagnement refusé</Typography.Text>;
  }

  if (etatDemande.id === ETAT_DEMANDE_VALIDEE) {
    return (
      <Space orientation="vertical">
        <Typography.Text type="success">Accompagnement validé</Typography.Text>
        {beneficiaire && beneficiaire.roles?.includes("ROLE_BENEFICIAIRE") && (
          <Button
            size="small"
            onClick={() => {
              navigate(`/beneficiaires/${demande.demandeur?.uid as string}`);
            }}
          >
            Voir bénéficiaire
          </Button>
        )}
      </Space>
    );
  }

  return (
    <Space orientation="vertical">
      Accompagnement à valider
      {questUtils?.isGrantedQuestionnaire(FONCTIONNALITES.STATUER_ACCOMPAGNEMENT) && (
        <ValidationAccompagnementButton demande={demande} />
      )}
    </Space>
  );
}
