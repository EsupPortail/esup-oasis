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

interface InfosComplementairesSectionProps {
  utilisateur: IUtilisateur;
  isFetching: boolean;
}

export const InfosComplementairesSection: React.FC<InfosComplementairesSectionProps> = ({
  utilisateur,
  isFetching,
}) => {
  if (!utilisateur.infosComplementaires || utilisateur.infosComplementaires.length === 0)
    return null;

  return (
    <>
      <h2>Informations complémentaires</h2>
      {isFetching ? (
        <Skeleton active paragraph />
      ) : (
        <Descriptions bordered column={1} style={{ overflowX: "auto" }}>
          {utilisateur.infosComplementaires.map((info, index) => (
            <Descriptions.Item
              key={`${info.libelle}-${index}`}
              label={info.libelle || "Information"}
            >
              <Typography.Text copyable={!!info.valeur}>
                {info.valeur || <MinusOutlined />}
              </Typography.Text>
            </Descriptions.Item>
          ))}
        </Descriptions>
      )}
    </>
  );
};
