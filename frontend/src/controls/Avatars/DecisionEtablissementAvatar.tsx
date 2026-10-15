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
import {
  CheckCircleFilled,
  CloseCircleFilled,
  FileDoneOutlined,
  FormOutlined,
  HourglassOutlined,
  SendOutlined,
} from "@ant-design/icons";
import { IDecisionEtablissement } from "@api";

export enum EtatDecisionEtablissement {
  EDITE = "EDITE",
  VALIDE = "VALIDE",
  ATTENTE_VALIDATION_CAS = "ATTENTE_VALIDATION_CAS",
  EDITION_DEMANDEE = "EDITION_DEMANDEE",
  EN_SIGNATURE = "EN_SIGNATURE",
  REFUSEE = "REFUSEE",
}

export function DecisionEtablissementAvatar(props: {
  utilisateurId?: string;
  decisionEtab?: IDecisionEtablissement;
  decisionEtabId?: string;
  className?: string;
  showLabel?: boolean;
  direction?: "horizontal" | "vertical";
}) {
  const { data: utilisateur } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: props.utilisateurId,
    enabled: !!props.utilisateurId,
  });

  const { data: decisionEtab } = useApi().useGetItem({
    path: "/utilisateurs/{uid}/decisions/{annee}",
    url: props.decisionEtabId,
    enabled: !!props.decisionEtabId,
  });

  const item = decisionEtab
    ? (decisionEtab as IDecisionEtablissement)
    : utilisateur
      ? (utilisateur?.decisionAmenagementAnneeEnCours as IDecisionEtablissement | undefined)
      : props.decisionEtab;

  switch (item?.etat) {
    case EtatDecisionEtablissement.ATTENTE_VALIDATION_CAS:
      return (
        <Tooltip title="En attente validation CAS">
          <Space size={2} orientation={props.direction}>
            <HourglassOutlined
              aria-hidden
              aria-label="En attente validation CAS"
              className={`text-warning fs-09 ${props.className}`}
            />
            {props.showLabel && <span className="legende">En attente CAS</span>}
          </Space>
        </Tooltip>
      );

    case EtatDecisionEtablissement.VALIDE:
      return (
        <Tooltip title="Validé CAS, à éditer">
          <Space size={2} orientation={props.direction}>
            <FileDoneOutlined
              aria-hidden
              aria-label="Validé CAS, à éditer"
              className={`text-warning fs-09 ${props.className}`}
            />
            {props.showLabel && <span className="legende">Validé CAS, à éditer</span>}
          </Space>
        </Tooltip>
      );

    case EtatDecisionEtablissement.EDITION_DEMANDEE:
      return (
        <Tooltip title="En cours d'envoi">
          <Space size={2} orientation={props.direction}>
            <SendOutlined
              aria-hidden
              aria-label="En cours d'envoi"
              className={`text-grey fs-09 ${props.className}`}
            />
            {props.showLabel && <span className="legende">En cours d'envoi</span>}
          </Space>
        </Tooltip>
      );

    case EtatDecisionEtablissement.EN_SIGNATURE:
      return (
        <Tooltip title="En signature électronique">
          <Space size={2} orientation={props.direction}>
            <FormOutlined
              aria-hidden
              aria-label="En signature électronique"
              className={`text-grey fs-09 ${props.className}`}
            />
            {props.showLabel && <span className="legende">En signature</span>}
          </Space>
        </Tooltip>
      );

    case EtatDecisionEtablissement.REFUSEE:
      return (
        <Tooltip title="Signature refusée, à reprendre">
          <Space size={2} orientation={props.direction}>
            <CloseCircleFilled
              aria-hidden
              aria-label="Signature refusée, à reprendre"
              className={`text-danger fs-09 ${props.className}`}
            />
            {props.showLabel && <span className="legende">Signature refusée</span>}
          </Space>
        </Tooltip>
      );

    case EtatDecisionEtablissement.EDITE:
      return (
        <Tooltip title="Éditée">
          <Space size={2} orientation={props.direction}>
            <CheckCircleFilled
              aria-hidden
              aria-label="Éditée"
              className={`text-success fs-09 ${props.className}`}
            />
            {props.showLabel && <span className="legende">Éditée</span>}
          </Space>
        </Tooltip>
      );

    default:
      return <></>;
  }
}
