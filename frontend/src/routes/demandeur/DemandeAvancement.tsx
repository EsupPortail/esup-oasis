/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Button, Card, Layout, Typography } from "antd";
import { useNavigate, useParams } from "react-router";

import AvancementDemande from "@controls/Demande/Avancement/AvancementDemande";
import { ETAT_ATTENTE_CHARTES } from "@lib";
import { ValidationCharte } from "@controls/Demande/ValidationCharte";
import Spinner from "@controls/Spinner/Spinner";
import { QuestionnaireProvider, useQuestionnaire } from "@context/demande/QuestionnaireProvider";

function Dossier(): React.ReactElement {
  const { demande } = useQuestionnaire();
  return (
    <>
      <Card className="mb-3" title={<h2 className="m-0">Avancement de votre demande</h2>}>
        <AvancementDemande demande={demande} informationEmail />
      </Card>
      {demande?.etat === ETAT_ATTENTE_CHARTES && <ValidationCharte demande={demande} />}
    </>
  );
}

/**
 * Page de gestion des demandes (demandeur)
 * @constructor
 */
export default function DemandeAvancement() {
  const { id } = useParams<"id">();
  const navigate = useNavigate();

  if (!id) return <Spinner />;

  return (
    <Layout.Content style={{ padding: "0 50px" }}>
      <Typography.Title level={1}>Demande</Typography.Title>
      <QuestionnaireProvider demandeId={`/demandes/${id}` as string}>
        <Dossier />
      </QuestionnaireProvider>
      <p className="mt-2 text-center">
        <Button type="primary" onClick={() => navigate("/demandes")}>
          Retour à la liste des demandes
        </Button>
      </p>
    </Layout.Content>
  );
}
