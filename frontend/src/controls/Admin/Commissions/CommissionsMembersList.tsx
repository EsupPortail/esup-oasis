/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useMemo, useState } from "react";
import {
  App,
  Button,
  Checkbox,
  Empty,
  List,
  Popconfirm,
  Skeleton,
  Space,
  Tag,
  Tooltip,
  Typography,
} from "antd";
import { DeleteOutlined, PlusOutlined, UserSwitchOutlined } from "@ant-design/icons";
import {
  ICommissionMembre,
  IUtilisateur,
  QK_COMMISSIONS_MEMBRES,
  QK_ROLES_UTILISATEURS,
} from "@api";
import { useApi } from "@context/api/ApiProvider";
import { RoleValues, Utilisateur } from "@lib";
import { UtilisateurAvatar } from "@controls/Avatars/UtilisateurAvatar";
import { useNavigate } from "react-router";
import { env } from "@/env";

import { CommissionsMemberAddForm } from "@controls/Admin/Commissions/CommissionsMemberAddForm";

interface CommissionsMembersListProps {
  commissionId: string | undefined;
}

function CommissionsEditionMembreRole(props: { membre: ICommissionMembre }) {
  const { message } = App.useApp();
  const [editing, setEditing] = useState<boolean>(false);
  const [attribuerProfil, setAttribuerProfil] = useState<boolean>(
    props.membre.roles?.includes(RoleValues.ROLE_ATTRIBUER_PROFIL) as boolean,
  );
  const [validerConformite, setValiderConformite] = useState<boolean>(
    props.membre.roles?.includes(RoleValues.ROLE_VALIDER_CONFORMITE_DEMANDE) as boolean,
  );

  const mutationPut = useApi().usePut({
    path: "/commissions/{commissionId}/membres/{uid}",
    invalidationQueryKeys: [QK_COMMISSIONS_MEMBRES, QK_ROLES_UTILISATEURS],
    onSuccess: () => {
      message.success("Le membre a bien été modifié").then();
      setEditing(false);
    },
  });

  if (editing) {
    return (
      <Space orientation="vertical" className="mt-2">
        <div>
          <Checkbox
            className="mr-1"
            checked={validerConformite}
            onChange={(e) => setValiderConformite(e.target.checked)}
          >
            Valider la conformité des demandes
          </Checkbox>
        </div>
        <div>
          <Checkbox
            className="mr-1"
            checked={attribuerProfil}
            onChange={(e) => setAttribuerProfil(e.target.checked)}
          >
            Attribuer le profil aux bénéficiaires
          </Checkbox>
        </div>
        <div className="mt-2">
          <Space>
            <Button
              type="primary"
              onClick={() => {
                mutationPut.mutate({
                  "@id": props.membre["@id"] as string,
                  data: {
                    roles: [
                      validerConformite ? RoleValues.ROLE_VALIDER_CONFORMITE_DEMANDE : undefined,
                      attribuerProfil ? RoleValues.ROLE_ATTRIBUER_PROFIL : undefined,
                    ].filter((r) => r !== undefined) as RoleValues[],
                  },
                });
              }}
            >
              Enregistrer
            </Button>
            <Button
              onClick={() => {
                setEditing(false);
              }}
            >
              Annuler
            </Button>
          </Space>
        </div>
      </Space>
    );
  }

  return (
    <Typography.Text
      type="secondary"
      editable={{
        tooltip: "Cliquez pour modifier les privilèges",
        onStart: () => {
          setEditing(true);
        },
      }}
    >
      {props.membre.roles?.includes(RoleValues.ROLE_VALIDER_CONFORMITE_DEMANDE) && (
        <Tag color="purple" className="mr-1">
          Valider conformité
        </Tag>
      )}
      {props.membre.roles?.includes(RoleValues.ROLE_ATTRIBUER_PROFIL) && (
        <Tag color="purple" className="mr-1">
          Attribuer profil
        </Tag>
      )}
      {!props.membre.roles?.includes(RoleValues.ROLE_VALIDER_CONFORMITE_DEMANDE) &&
        !props.membre.roles?.includes(RoleValues.ROLE_ATTRIBUER_PROFIL) &&
        "Aucun privilège"}
    </Typography.Text>
  );
}

function CommissionsEditionMembre(props: { membre: ICommissionMembre }) {
  const { message } = App.useApp();
  const navigate = useNavigate();
  const { data: membre, isFetching } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: props.membre.utilisateur,
    enabled: !!props.membre.utilisateur,
  });

  const mutationDelete = useApi().useDelete({
    path: "/commissions/{commissionId}/membres/{uid}",
    invalidationQueryKeys: [QK_COMMISSIONS_MEMBRES],
    onSuccess: () => {
      message.success("Le membre a bien été retiré de la commission").then();
    },
  });

  const membreUtilisateur = useMemo(
    () => (membre ? new Utilisateur(membre as IUtilisateur) : undefined),
    [membre],
  );
  if (isFetching) return <Skeleton paragraph={{ rows: 1 }} active />;
  if (!membre) return null;

  return (
    <List.Item
      actions={[
        env.REACT_APP_ENVIRONMENT !== "production" ? (
          <Popconfirm
            key="impersonate"
            title="Êtes-vous sûr de vouloir prendre l'identité de cet utilisateur ?"
            onConfirm={() => {
              navigate(`/impersonate/${membre.uid}`);
            }}
            okText="Oui"
            cancelText="Non"
            placement="left"
          >
            <Tooltip title="Prendre l'identité">
              <Button icon={<UserSwitchOutlined aria-hidden />} />
            </Tooltip>
          </Popconfirm>
        ) : null,
        <Popconfirm
          key="delete"
          title="Voulez-vous vraiment retirer ce membre de la commission ?"
          onConfirm={() => {
            mutationDelete.mutate({
              "@id": props.membre["@id"] as string,
            });
          }}
          okText="Oui"
          okType="danger"
          cancelText="Non"
        >
          <Button
            icon={<DeleteOutlined aria-hidden />}
            className="btn-danger-hover btn-label-hover"
          >
            Supprimer
          </Button>
        </Popconfirm>,
      ].filter(Boolean)}
    >
      <List.Item.Meta
        title={`${membre?.nom} ${membre?.prenom}`}
        description={<CommissionsEditionMembreRole membre={props.membre} />}
        avatar={
          <UtilisateurAvatar
            utilisateur={membre}
            role={membreUtilisateur?.roleCalcule as RoleValues}
          />
        }
      />
    </List.Item>
  );
}

export const CommissionsMembersList: React.FC<CommissionsMembersListProps> = ({ commissionId }) => {
  const [ajouterMembre, setAjouterMembre] = useState<boolean>(false);
  const { data: membres, isFetching } = useApi().useGetFullCollection({
    path: "/commissions/{commissionId}/membres",
    parameters: {
      commissionId: commissionId as string,
    },
    enabled: !!commissionId,
  });

  if (isFetching) return <Skeleton paragraph={{ rows: 4 }} active />;

  if (!membres) return null;

  return (
    <>
      {ajouterMembre && (
        <CommissionsMemberAddForm
          commissionId={commissionId}
          onCancel={() => setAjouterMembre(false)}
          onSuccess={() => setAjouterMembre(false)}
        />
      )}
      <List
        className="ant-list-radius"
        header={
          commissionId && (
            <div className="text-right">
              <Button
                key="ajouterMembre"
                type="primary"
                icon={<PlusOutlined aria-hidden />}
                onClick={() => {
                  setAjouterMembre(true);
                }}
              >
                Ajouter un membre
              </Button>
            </div>
          )
        }
      >
        {membres.items.map((membre) => (
          <CommissionsEditionMembre key={membre["@id"]} membre={membre} />
        ))}
        {membres.items.length === 0 && (
          <List.Item>
            <Empty
              image={Empty.PRESENTED_IMAGE_SIMPLE}
              className="m-auto"
              description="Aucun membre dans cette commission"
            />
          </List.Item>
        )}
      </List>
    </>
  );
};
