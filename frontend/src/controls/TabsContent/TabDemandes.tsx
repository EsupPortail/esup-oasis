/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { Button, Empty, Flex, List, Space, Tooltip } from "antd";
import React, { ReactElement, useState } from "react";
import { IDemande, IUtilisateur } from "@api";
import { useApi } from "@context/api/ApiProvider";
import { TypeDemandeItem } from "@controls/Items/TypeDemandeItem";
import { EtatDemandeAvatar } from "@controls/Avatars/EtatDemandeAvatar";
import dayjs from "dayjs";
import Icon, { EyeOutlined, PlusOutlined } from "@ant-design/icons";
import { useNavigate } from "react-router-dom";
import ExternalLink from "@/assets/images/external-link.svg?react";
import NouvelleDemandeModaleGestionnaire from "@controls/Modals/Demande/NouvelleDemandeModaleGestionnaire";

interface ITabDemandesProps {
  utilisateur: IUtilisateur;
  title: React.ReactElement;
}

interface ITabDemandesItemProps {
  demande: IDemande;
}

/**
 * Renders a single item in the TabDemandes component.
 *
 * @param {ITabDemandesItemProps} props - The props object.
 * @param {IDemande} props.demande - The inscription object containing information about the item.
 *
 * @return {ReactElement} - The rendered item component.
 */
function TabDemandesItem({ demande }: ITabDemandesItemProps): ReactElement {
  const navigate = useNavigate();
  return (
    <List.Item
      onClick={() => navigate(`/demandes/${demande.id}`)}
      className="pointer"
      extra={
        <Space.Compact>
          <Button icon={<EyeOutlined />} onClick={() => navigate(`/demandes/${demande.id}`)}>
            Voir
          </Button>
          <Tooltip title="Ouvrir dans un nouvel onglet">
            <Button
              className="text-light"
              icon={<Icon component={ExternalLink} className="fs-08" />}
              onClick={(e) => {
                e.stopPropagation();
                window.open(`/demandes/${demande.id}`, "_blank");
              }}
            />
          </Tooltip>
        </Space.Compact>
      }
    >
      <List.Item.Meta
        title={<TypeDemandeItem typeDemandeId={demande.typeDemande} showAvatar={false} showInfos />}
        description={
          demande.dateDepot && (
            <Space orientation="vertical">
              <Space>
                <span>Date de dépôt</span>
                <span className="semi-bold">{dayjs(demande.dateDepot).format("DD/MM/YYYY")}</span>
              </Space>
            </Space>
          )
        }
        avatar={<EtatDemandeAvatar etatDemandeId={demande.etat} />}
      />
    </List.Item>
  );
}

/**
 * Renders the "TabDemandes" component.
 * This component displays the user's registrations.
 *
 * @param {ITabDemandesProps} props - The component props.
 * @param {IUtilisateur} props.utilisateur - The user object containing the registrations.
 *
 * @returns {ReactElement} The rendered component.
 */
export function TabDemandes({ utilisateur, title }: ITabDemandesProps): ReactElement {
  const [nouvelleDemande, setNouvelleDemande] = useState<boolean>(false);
  const { data: demandes, isFetching: fetchingDemandes } = useApi().useGetFullCollection({
    path: "/demandes",
    query: {
      demandeur: utilisateur["@id"],
      "order[dateDepot]": "desc",
      format_simple: true,
    },
  });

  return (
    <>
      <Flex justify="space-between" align="center" className="mb-2" wrap>
        {title}
        <>
          <Button
            icon={<PlusOutlined aria-hidden />}
            type="primary"
            onClick={() => setNouvelleDemande(true)}
          >
            Nouvelle demande
          </Button>
        </>
      </Flex>
      <NouvelleDemandeModaleGestionnaire
        open={nouvelleDemande}
        setOpen={setNouvelleDemande}
        demandeurId={utilisateur["@id"]}
      />
      {!demandes || demandes?.items?.length === 0 ? (
        <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Aucune demande" />
      ) : (
        <List loading={fetchingDemandes} className="ant-list-radius ant-list-animated">
          {demandes?.items?.map((demande) => (
            <TabDemandesItem key={demande?.["@id"]} demande={demande} />
          ))}
        </List>
      )}
    </>
  );
}
