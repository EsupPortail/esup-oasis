/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Button, Layout, Tooltip, Typography } from "antd";
import { useParams } from "react-router-dom";
import { QuestionCircleOutlined } from "@ant-design/icons";
import Spinner from "@controls/Spinner/Spinner";
import { QuestionnaireProvider } from "@context/demande/QuestionnaireProvider";
import { TypeDemandeContent } from "@controls/Questionnaire/TypeDemandeContent";
import { env } from "@/env";
import { service } from "@lib";

/**
 * Page de gestion des demandes (demandeur)
 * @constructor
 */
export default function DemandeSaisie() {
  const { id } = useParams<"id">();

  if (!id) return <Spinner />;

  return (
    <Layout.Content style={{ padding: "0 50px" }}>
      <Typography.Title level={1}>
        Demande
        <Tooltip title={`Envoyer un email ${service.a} pour obtenir de l'aide`}>
          <Button
            className="float-right mt-2"
            onClick={() => window.open(`mailto:${env.REACT_APP_EMAIL_SERVICE}`)}
            icon={<QuestionCircleOutlined aria-hidden />}
            aria-label={`Envoyer un email ${service.a} pour obtenir de l'aide`}
          >
            Besoin d'aide
          </Button>
        </Tooltip>
      </Typography.Title>
      <QuestionnaireProvider demandeId={`/demandes/${id}`}>
        <TypeDemandeContent />
      </QuestionnaireProvider>
    </Layout.Content>
  );
}
