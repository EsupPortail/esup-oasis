/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Col, Descriptions, Flex, Skeleton, Typography } from "antd";
import { MinusOutlined } from "@ant-design/icons";
import { IUtilisateur } from "@api";
import { ScolariteListItem } from "@controls/TabsContent/TabScolarite";

interface ScolariteSectionProps {
  utilisateur: IUtilisateur;
  isFetching: boolean;
}

export const ScolariteSection: React.FC<ScolariteSectionProps> = ({ utilisateur, isFetching }) => {
  return (
    <Col xs={24} xl={12}>
      <h2>Scolarité</h2>
      {isFetching ? (
        <Skeleton active paragraph />
      ) : (
        <Descriptions bordered column={1} style={{ overflowX: "auto" }}>
          <Descriptions.Item label="Numéro étudiant">
            <Typography.Text copyable={utilisateur.numeroEtudiant !== undefined}>
              {utilisateur.numeroEtudiant || <MinusOutlined />}
            </Typography.Text>
          </Descriptions.Item>
          <Descriptions.Item label="Régime d'inscription">
            {utilisateur?.statutEtudiant}
          </Descriptions.Item>
          <Descriptions.Item label="Inscriptions" styles={{ label: { width: 200 } }}>
            <h3 className="sr-only">Inscriptions</h3>
            <Flex vertical style={{ width: "100%", overflowY: "auto" }} wrap="wrap">
              {[...(utilisateur.inscriptions || [])]
                .sort((i1, i2) => (i2.debut || "").localeCompare(i1.debut || ""))
                .map((i) => (
                  <ScolariteListItem key={i["@id"]} inscription={i} titleClassName="" />
                ))}
            </Flex>
          </Descriptions.Item>
        </Descriptions>
      )}
    </Col>
  );
};
