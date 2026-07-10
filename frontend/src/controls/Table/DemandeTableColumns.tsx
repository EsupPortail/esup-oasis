/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { ascToAscend } from "@utils/array";
import { RoleValues, Utilisateur } from "@lib";
import { IDemande, IEtatDemande, IUtilisateur } from "@api";
import { ColumnType } from "antd/es/table";
import { Button, Flex, Popconfirm, Space, Tooltip } from "antd";
import { FilterProps } from "@utils/table";
import { ComposanteItem } from "@controls/Items/ComposanteItem";
import { TypeDemandeItem } from "@controls/Items/TypeDemandeItem";
import dayjs from "dayjs";
import { EtatDemandeAvatar } from "@controls/Avatars/EtatDemandeAvatar";
import Icon, { EyeOutlined, MinusOutlined, UserSwitchOutlined } from "@ant-design/icons";
import React from "react";
import { FiltreDemande } from "@controls/Table/DemandeTable";
import ExternalLink from "@/assets/images/external-link.svg?react";
import UtilisateurAvatarImage from "@controls/Avatars/UtilisateurAvatarImage";
import { EllipsisMiddle } from "@controls/Typography/EllipsisMiddle";
import Highlighter from "react-highlight-words";
import { removeAccents } from "@utils/string";

import { UseStateDispatch } from "@utils/utils";
import { env } from "@/env";

export function demandeTableColumns({
  filter,
  setFilter,
  user,
  onImpersonate,
  onDemandeSelected,
}: {
  filter: FiltreDemande;
  setFilter: UseStateDispatch<FiltreDemande>;
  user: Utilisateur | undefined;
  etats: IEtatDemande[] | undefined;
  onImpersonate: (uid: string) => void;
  onDemandeSelected: (demande: IDemande) => void;
}): ColumnType<IDemande>[] {
  function canImpersonate() {
    return user?.isAdmin && env.REACT_APP_ENVIRONMENT !== "production";
  }

  // noinspection JSUnusedGlobalSymbols
  const clickableCell = {
    className: "pointer",
    onCell: (record: IDemande) => {
      return {
        onClick: () => {
          onDemandeSelected(record);
        },
      };
    },
  };

  return [
    {
      title: "Demandeur",
      dataIndex: "demandeur.nom",
      key: "demandeur.nom",
      render: (_value, record) => {
        return (
          <Flex justify="space-between" align="center">
            <Space>
              <UtilisateurAvatarImage
                as="img"
                utilisateur={record.demandeur as IUtilisateur}
                width={48}
                size={48}
                role={RoleValues.ROLE_DEMANDEUR}
                className="border-0"
                responsive="lg"
              />
              <span>
                <span className="bold">
                  <Highlighter
                    textToHighlight={(record.demandeur?.nom || "").toLocaleUpperCase()}
                    searchWords={[removeAccents(filter["demandeur.nom"] || "")]}
                  />
                </span>{" "}
                <span className="light">{record.demandeur?.prenom}</span>
              </span>
            </Space>
            <Tooltip title="Ouvrir dans un nouvel onglet">
              <Button
                size="small"
                type="text"
                className="text-light"
                icon={<Icon component={ExternalLink} className="fs-08" />}
                onClick={(e) => {
                  e.stopPropagation();
                  window.open(`/demandes/${record.id}`, "_blank");
                }}
              />
            </Tooltip>
          </Flex>
        );
      },
      fixed: "left",
      sortDirections: ["ascend", "descend"],
      sorter: true,
      defaultSortOrder: "ascend",
      sortOrder: ascToAscend(filter?.["order[demandeur.nom]"]),
      ...FilterProps<FiltreDemande>("demandeur.nom", filter, setFilter),
      filteredValue: filter?.["demandeur.nom"] ? [filter?.["demandeur.nom"]] : null,
      ...clickableCell,
    } as ColumnType<IDemande>,
    {
      title: "Type de demande",
      dataIndex: "campagne.typeDemande[]",
      key: "campagne.typeDemande[]",
      responsive: ["xl"],
      render: (_value, record) => (
        <TypeDemandeItem
          className="semi-bold"
          typeDemandeId={record.typeDemande}
          showInfos
          showAvatar={false}
          as="Flex"
        />
      ),
    } as ColumnType<IDemande>,
    {
      title: "Composante",
      dataIndex: "composante[]",
      key: "composante[]",
      responsive: ["lg"],
      width: 125,
      render: (_value, record) => {
        const composante = [...(record.demandeur?.inscriptions || [])].sort((i1, i2) =>
          (i2.debut || "").localeCompare(i1.debut || ""),
        )[0]?.formation?.composante;
        if (composante) {
          return <ComposanteItem composanteId={composante} ellipsis />;
        }
        return null;
      },
    } as ColumnType<IDemande>,
    {
      title: "Formation",
      dataIndex: "formation[]",
      key: "formation[]",
      responsive: ["xl"],
      width: 200,
      render: (_value, record) => {
        const libelleFormation = [...(record.demandeur?.inscriptions || [])].sort((i1, i2) =>
          (i2.debut || "").localeCompare(i1.debut || ""),
        )[0]?.formation?.libelle;
        return (
          <EllipsisMiddle
            className="light"
            style={{ maxWidth: 200 }}
            suffixCount={12}
            content={libelleFormation || ""}
            expandable
          />
        );
      },
    } as ColumnType<IDemande>,
    {
      title: "Dépôt",
      dataIndex: "dateDepot",
      key: "dateDepot",
      sortDirections: ["ascend", "descend"],
      sorter: true,
      sortOrder: ascToAscend(filter?.["order[dateDepot]"]),
      responsive: ["xl"],
      render: (_value, record) =>
        record.dateDepot ? dayjs(record.dateDepot).format("DD/MM/YYYY") : <MinusOutlined />,
      filteredValue: null,
    } as ColumnType<IDemande>,
    {
      title: "État",
      dataIndex: "etat",
      key: "etat",
      render: (_value, record) => {
        return <EtatDemandeAvatar etatDemandeId={record.etat} />;
      },
    } as ColumnType<IDemande>,
    {
      key: "actions",
      className: "text-right",
      width: 160,
      render: (_value, record: IDemande) => (
        <Space>
          {canImpersonate() && (
            <Popconfirm
              title="Êtes-vous sûr•e de vouloir prendre l'identité de cet utilisateur ?"
              onConfirm={() => {
                onImpersonate(record.demandeur?.uid as string);
              }}
              okText="Oui"
              cancelText="Non"
              placement="left"
            >
              <Tooltip title="Prendre l'identité">
                <Button icon={<UserSwitchOutlined aria-hidden />} />
              </Tooltip>
            </Popconfirm>
          )}
          <Space.Compact>
            <Button
              icon={<EyeOutlined />}
              onClick={() => {
                onDemandeSelected(record);
              }}
            >
              Voir
            </Button>
            <Tooltip title="Ouvrir dans un nouvel onglet">
              <Button
                className="text-light"
                icon={<Icon component={ExternalLink} className="fs-08" />}
                onClick={() => {
                  window.open(`/demandes/${record.id}`, "_blank");
                }}
              />
            </Tooltip>
          </Space.Compact>
        </Space>
      ),
      filteredValue: null,
    },
  ];
}
