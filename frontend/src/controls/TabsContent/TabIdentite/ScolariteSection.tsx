/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Descriptions, Skeleton, Typography } from "antd";
import { MinusOutlined } from "@ant-design/icons";
import { IUtilisateur } from "@api";
import { InscriptionsDetail } from "@controls/Items/InscriptionsDetail";

interface ScolariteSectionProps {
  utilisateur: IUtilisateur;
  isFetching: boolean;
}

export const ScolariteSection: React.FC<ScolariteSectionProps> = ({ utilisateur, isFetching }) => {
  return (
    <>
      <h2>Scolarité</h2>
      {isFetching ? (
        <Skeleton active paragraph />
      ) : (
        <Descriptions bordered column={1} style={{ overflowX: "auto" }}>
          <Descriptions.Item label="Numéro étudiant">
            <Typography.Text copyable={!!utilisateur.numeroEtudiant}>
              {utilisateur.numeroEtudiant || <MinusOutlined />}
            </Typography.Text>
          </Descriptions.Item>
          <Descriptions.Item label="Régime d'inscription">
            {utilisateur?.statutEtudiant}
          </Descriptions.Item>
          <Descriptions.Item label="Inscriptions" styles={{ label: { width: 200 } }}>
            <h3 className="sr-only">Inscriptions</h3>
            <InscriptionsDetail inscriptions={utilisateur.inscriptions} />
          </Descriptions.Item>
        </Descriptions>
      )}
    </>
  );
};
