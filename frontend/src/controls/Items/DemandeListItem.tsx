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
import { IDemande } from "@api";
import { useApi } from "@context/api/ApiProvider";
import Spinner from "@controls/Spinner/Spinner";
import { Badge, Button, List, Space } from "antd";
import TypeDemandeAvatar from "@controls/Avatars/TypeDemandeAvatar";
import {
  ETAT_ATTENTE_CHARTES,
  ETAT_DEMANDE_EN_COURS,
  ETAT_DEMANDE_NON_CONFORME,
  ETAT_DEMANDE_REFUSEE,
} from "@lib";
import { useNavigate } from "react-router";
import { EtatDemandeAvatar } from "@controls/Avatars/EtatDemandeAvatar";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { CampagneDemandeDateItem } from "./CampagneDemandeDateItem";

function CampagneItem(props: { campagneId?: string }) {
  let templateString: string;
  const { data: campagneDemandeData } = useApi().useGetItem({
    path: "/types_demandes/{typeId}/campagnes/{id}",
    url: props.campagneId,
    enabled: !!props.campagneId,
  });

  if (!props.campagneId || !campagneDemandeData) return <Spinner />;

  // Si la campagne est passée
  if (campagneDemandeData?.fin && new Date(campagneDemandeData.fin) < new Date()) {
    templateString = "Campagne fermée depuis le {{fin}}";
  } else if (campagneDemandeData?.debut && new Date(campagneDemandeData.debut) > new Date()) {
    // Si la campagne est à venir
    templateString = "Prochaine campagne du {{debut}} au {{fin}}";
  } else {
    // Si la campagne est en cours
    templateString = "Campagne ouverte du {{debut}} au {{fin}}";
  }

  return campagneDemandeData ? (
    <CampagneDemandeDateItem
      campagneDemande={campagneDemandeData}
      templateString={templateString}
    />
  ) : (
    <Spinner />
  );
}

export function DemandeListItem(props: { demande?: IDemande; demandeId?: string }) {
  const screens = useBreakpoint();
  const navigate = useNavigate();
  const { data: demandeData } = useApi().useGetItem({
    path: "/demandes/{id}",
    url: props.demandeId as string,
    enabled: !!props.demandeId,
  });
  const item = props.demande ?? demandeData;
  const { data: typeDemandeData } = useApi().useGetItem({
    path: "/types_demandes/{id}",
    url: item?.typeDemande as string,
    enabled: !!item?.typeDemande,
  });

  if (!item || !typeDemandeData) return <Spinner />;

  function getActionButton() {
    switch (item?.etat) {
      case ETAT_DEMANDE_EN_COURS:
        return (
          <Button
            onClick={() => navigate(`/demandes/${item?.id}/saisie`)}
            aria-label={`Continuer la saisie de la demande : ${typeDemandeData?.libelle}`}
          >
            Continuer la saisie
          </Button>
        );
      case ETAT_DEMANDE_NON_CONFORME:
        return (
          <Button
            onClick={() => navigate(`/demandes/${item?.id}/saisie`)}
            aria-label={`Reprendre la saisie de la demande : ${typeDemandeData?.libelle}`}
          >
            Reprendre la saisie
          </Button>
        );
      case ETAT_ATTENTE_CHARTES:
        return (
          <Badge dot status="processing">
            <Button
              className="semi-bold"
              onClick={() => navigate(`/demandes/${item?.id}`)}
              aria-label={`Valider la charte de la demande : ${typeDemandeData?.libelle}`}
            >
              Valider la charte
            </Button>
          </Badge>
        );
      default:
        return (
          <Button
            onClick={() => navigate(`/demandes/${item?.id}`)}
            aria-label={`Suivre l'avancement de la demande : ${typeDemandeData?.libelle}`}
          >
            Suivre ma demande
          </Button>
        );
    }
  }

  return (
    <List.Item actions={screens.md ? [getActionButton()] : undefined}>
      <List.Item.Meta
        avatar={<TypeDemandeAvatar typeDemande={typeDemandeData} />}
        title={typeDemandeData.libelle}
        description={
          <>
            <Space orientation="vertical" size={3}>
              <Space align="start" className="mb-1">
                <EtatDemandeAvatar
                  etatDemandeId={item.etat}
                  afficherDerniereModification={
                    item.etat === ETAT_DEMANDE_NON_CONFORME || item.etat === ETAT_DEMANDE_REFUSEE
                  }
                  demandeId={item["@id"]}
                />
              </Space>
              <CampagneItem campagneId={item?.campagne} />
              {!screens.md && getActionButton()}
            </Space>
          </>
        }
      />
    </List.Item>
  );
}
