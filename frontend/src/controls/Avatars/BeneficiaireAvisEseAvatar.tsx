/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { useApi } from "@context/api/ApiProvider";
import { Space, Tooltip } from "antd";
import { CheckCircleFilled, HourglassOutlined } from "@ant-design/icons";
import { env } from "@/env";

export enum EtatAvisEse {
  ETAT_EN_COURS = "EN_COURS",
  ETAT_EN_ATTENTE = "EN_ATTENTE",
  ETAT_AUCUN = "AUCUN",
}

export function BeneficiaireAvisEseAvatar(props: {
  utilisateurId?: string;
  etatAvisEse?: EtatAvisEse;
  className?: string;
  showLabel?: boolean;
  direction?: "horizontal" | "vertical";
}) {
  const { data: utilisateur } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: props.utilisateurId,
    enabled: !!props.utilisateurId,
  });
  const item = (utilisateur?.etatAvisEse as EtatAvisEse | undefined) ?? props.etatAvisEse;

  switch (item) {
    case EtatAvisEse.ETAT_EN_ATTENTE:
      return (
        <Tooltip title={`Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} en attente`}>
          <Space size={2} orientation={props.direction}>
            <HourglassOutlined
              aria-hidden
              aria-label={`Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} en attente`}
              className={`text-warning fs-09 ${props.className}`}
            />
            {props.showLabel && <span className="legende">En attente</span>}
          </Space>
        </Tooltip>
      );

    case EtatAvisEse.ETAT_EN_COURS:
      return (
        <Tooltip title={`Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} en cours`}>
          <Space size={2} orientation={props.direction}>
            <CheckCircleFilled
              aria-hidden
              aria-label={`Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} en cours`}
              className={`text-success fs-09 ${props.className}`}
            />
            {props.showLabel && <span className="legende">En cours</span>}
          </Space>
        </Tooltip>
      );

    default:
      return <></>;
  }
}
