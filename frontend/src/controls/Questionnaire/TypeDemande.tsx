/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { QuestionnaireProvider } from "@context/demande/QuestionnaireProvider";
import { TypeDemandeContent } from "@controls/Questionnaire/TypeDemandeContent";
import { useParams } from "react-router";
import { Alert } from "antd";
import { ExclamationOutlined } from "@ant-design/icons";

/**
 * @function TypeDemande
 * @returns {React.ReactElement} The TypeDemande component with the TypeDemandeProvider and TypeDemandeContent.
 */
export default function TypeDemande(): React.ReactElement {
  // Get the id from the URL
  const { questionnaireId } = useParams<"questionnaireId">();

  if (!questionnaireId) {
    return (
      <Alert
        type="error"
        icon={<ExclamationOutlined />}
        showIcon
        title="Erreur"
        description="Erreur lors de la récupération du questionnaire."
      />
    );
  }

  // Return the TypeDemande in his provider
  return (
    <QuestionnaireProvider typeDemandeId={`/types_demandes/${questionnaireId}`}>
      <TypeDemandeContent />
    </QuestionnaireProvider>
  );
}
