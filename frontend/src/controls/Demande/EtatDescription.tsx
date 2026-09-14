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
import { Button, Space } from "antd";
import { useNavigate } from "react-router-dom";
import { IDemande } from "@api";
import {
  ETAT_ATTENTE_ACCOMPAGNEMENT,
  ETAT_ATTENTE_CHARTES,
  ETAT_DEMANDE_ATTENTE_COMMISSION,
  ETAT_DEMANDE_CONFORME,
  ETAT_DEMANDE_EN_COURS,
  ETAT_DEMANDE_RECEPTIONNEE,
  ETAT_DEMANDE_REFUSEE,
  ETAT_DEMANDE_VALIDEE,
  ETATS_DEMANDES,
  service,
} from "@lib";

interface IEtatDescriptionProps {
  demande: IDemande;
}

export function EtatDescription({ demande }: IEtatDescriptionProps) {
  const navigate = useNavigate();
  let description = ETATS_DEMANDES.find((e) => e.id === demande.etat)?.description;

  if (!description) return null;

  const article = "Votre";
  description = description
    .replaceAll("{article}", article.toLocaleLowerCase())
    .replaceAll("{Article}", article.charAt(0).toUpperCase() + article.slice(1))
    .replaceAll("{service}", service.denomination)
    .replaceAll("{le-service}", service.defini)
    .replaceAll("{du-service}", service.de)
    .replaceAll("{au-service}", service.a)
    .replaceAll("{contexte}", "vous ");

  switch (demande.etat) {
    case ETAT_DEMANDE_EN_COURS:
      return (
        <Space orientation="vertical">
          <span>{description}.</span>
          <Button
            className="mt-2"
            type="primary"
            onClick={() => navigate(`/demandes/${demande.id}/saisie`)}
          >
            Reprendre la saisie
          </Button>
        </Space>
      );
    case ETAT_DEMANDE_RECEPTIONNEE:
    case ETAT_DEMANDE_CONFORME:
    case ETAT_DEMANDE_VALIDEE:
    case ETAT_DEMANDE_REFUSEE:
    case ETAT_DEMANDE_ATTENTE_COMMISSION:
    case ETAT_ATTENTE_CHARTES:
    case ETAT_ATTENTE_ACCOMPAGNEMENT:
      return description;
  }
}
