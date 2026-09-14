/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Button, Card, Dropdown, Form, Input, MenuProps, Popconfirm, Select, Space } from "antd";
import { ROLE_INCONNU, ROLES_SELECT, RoleValues, service, Utilisateur } from "@lib";
import React, { ReactElement, useEffect, useState } from "react";
import { useApi } from "@context/api/ApiProvider";
import { DeleteOutlined, DownOutlined, EditOutlined, SaveOutlined } from "@ant-design/icons";
import { IUtilisateur, QK_ROLES_UTILISATEURS } from "@api";

interface IUtilisateursEditer {
  utilisateur: IUtilisateur;
  onEdited: (utilisateur?: IUtilisateur) => void;
}

/**
 * Edits a user.
 *
 * @param {Object} params - The parameters for editing the user.
 * @param {IUtilisateur} params.utilisateur - The user to edit.
 * @param {Function} params.onEdited - The callback function to be called after the user is edited.
 * @returns {ReactElement} The form for editing the user.
 */
export function UtilisateurEditer({ utilisateur, onEdited }: IUtilisateursEditer): ReactElement {
  const [form] = Form.useForm<Utilisateur>();
  const [rolesSelectionnes, setRolesSelectionnes] = useState(utilisateur.roles);
  const { data: dataServices, isFetching: isFetchingServices } = useApi().useGetFullCollection({
    path: "/services",
  });
  const mutationPatch = useApi().usePatch({
    path: "/utilisateurs/{uid}",
    invalidationQueryKeys: [QK_ROLES_UTILISATEURS],
    onSuccess: (data) => {
      if (onEdited) onEdited(data);
    },
  });

  useEffect(() => {
    const user = new Utilisateur(utilisateur);
    form.resetFields();
    form.setFieldsValue({
      ...user,
      roles: user.roles.filter((r) => ROLES_SELECT.map((ru) => ru.value as string).includes(r)),
    });
  }, [form, utilisateur]);

  const itemsMenu: MenuProps["items"] = [
    {
      key: "supprimer",
      label: (
        <Popconfirm
          title="Supprimer cet utilisateur ?"
          okButtonProps={{ danger: true }}
          okText="Oui, supprimer"
          cancelText="Non"
          onConfirm={() => {
            mutationPatch.mutate({
              data: {
                ...utilisateur,
                nom: undefined,
                prenom: undefined,
                email: undefined,
                roles: [],
                services: [],
              } as IUtilisateur,
              "@id": utilisateur["@id"] as string,
            });
          }}
        >
          Supprimer
        </Popconfirm>
      ),
      icon: <DeleteOutlined />,
      danger: true,
    },
  ];

  function roleRequis() {
    return (
      (rolesSelectionnes?.includes(RoleValues.ROLE_GESTIONNAIRE) &&
        !rolesSelectionnes?.includes(RoleValues.ROLE_ADMIN)) ||
      rolesSelectionnes?.includes(RoleValues.ROLE_RENFORT)
    );
  }

  return (
    <Card
      title={
        <Space>
          <EditOutlined />
          Editer
        </Space>
      }
      extra={
        <Dropdown menu={{ items: itemsMenu }}>
          <Space>
            <Button className="no-hover" type="text" icon={<DownOutlined />}>
              Options
            </Button>
          </Space>
        </Dropdown>
      }
    >
      <Form<Utilisateur>
        form={form}
        layout="vertical"
        onFinish={(values) => {
          mutationPatch.mutate({
            "@id": utilisateur["@id"] as string,
            data: {
              ...values,
              nom: undefined,
              prenom: undefined,
              email: undefined,
            } as IUtilisateur,
          });
        }}
      >
        <Form.Item name="nom" label="Nom">
          <Input disabled />
        </Form.Item>
        <Form.Item name="prenom" label="Prénom">
          <Input disabled />
        </Form.Item>
        <Form.Item name="email" label="Email">
          <Input disabled />
        </Form.Item>
        <Form.Item
          name="roles"
          label="Rôle"
          rules={[
            {
              required: true,
              message: "Veuillez sélectionner un rôle",
            },
            {
              validator: (_, value) => {
                if (value === ROLE_INCONNU) {
                  return Promise.reject("Veuillez sélectionner un rôle");
                }
                return Promise.resolve();
              },
            },
          ]}
        >
          <Select
            mode="tags"
            options={ROLES_SELECT}
            onChange={(value) => {
              setRolesSelectionnes(value);
            }}
          />
        </Form.Item>
        <Form.Item
          name="services"
          className="mt-2"
          label={`Bureaux ${service.sigle}`}
          required={roleRequis()}
          rules={
            roleRequis()
              ? [
                  {
                    required: true,
                    message: "Veuillez sélectionner au moins un bureau",
                  },
                ]
              : undefined
          }
        >
          <Select
            mode="multiple"
            loading={isFetchingServices}
            showSearch={false}
            options={dataServices?.items
              .filter((s) => s.actif)
              .map((s) => ({
                label: s.libelle,
                value: s["@id"],
              }))}
          />
        </Form.Item>
        <div className="text-center mt-3">
          <Button
            className="mr-2"
            onClick={() => {
              form.resetFields();
              onEdited(undefined);
            }}
          >
            Annuler
          </Button>
          <Button type="primary" icon={<SaveOutlined />} htmlType="submit">
            Enregistrer
          </Button>
        </div>
      </Form>
    </Card>
  );
}
