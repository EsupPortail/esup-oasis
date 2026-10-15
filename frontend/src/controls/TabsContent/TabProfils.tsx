/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Button, Form, Popconfirm } from "antd";
import { DeleteOutlined, PlusOutlined } from "@ant-design/icons";
import BeneficiaireProfilCardItem from "@controls/Forms/BeneficiaireProfilCardItem";
import { useApi } from "@context/api/ApiProvider";
import { queryClient } from "@/queryClient";
import { IUtilisateur, QK_BENEFICIAIRES, QK_STATISTIQUES_EVENEMENTS } from "@api";

interface ITabProfilsProps {
  utilisateur: IUtilisateur;
  title?: React.ReactElement;
}

/**
 * Renders a list of profiles for a beneficiary.
 * The component also provides a button to add a new profile and a button to delete a profile.
 *
 * @param utilisateur - The beneficiary object.
 * @param title
 * @returns The rendered component.
 */
export function TabProfils({ utilisateur, title }: ITabProfilsProps): ReactElement {
  const mutationDeleteProfil = useApi().useDelete({
    path: "/utilisateurs/{uid}/profils/{id}",
    invalidationQueryKeys: [QK_BENEFICIAIRES, QK_STATISTIQUES_EVENEMENTS],
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [utilisateur["@id"]] }).then();
    },
  });
  return (
    <>
      {title}
      <Form.List name="profils">
        {(fields, { add, remove }, { errors }) => (
          <>
            {fields.map(({ key, ...field }) => (
              <Form.Item className="mb-2" required key={key}>
                <Form.Item
                  {...field}
                  validateTrigger={["onChange", "onBlur"]}
                  rules={[
                    {
                      required: true,
                      whitespace: true,
                      message: "Un profil est requis ou supprimez ce champ.",
                    },
                  ]}
                  noStyle
                >
                  <BeneficiaireProfilCardItem
                    style={{ width: "calc(100% - 35px)" }}
                    className="mt-1 mb-1"
                    utilisateur={utilisateur}
                    extra={
                      fields.length > 1 ? (
                        <Popconfirm
                          title="Supprimer le profil ?"
                          okText="Oui"
                          okButtonProps={{ danger: true }}
                          cancelText="Non"
                          onConfirm={() => {
                            mutationDeleteProfil.mutate({
                              "@id": utilisateur.profils?.at(key) as string,
                            });
                          }}
                        >
                          <Button
                            type="text"
                            icon={<DeleteOutlined />}
                            className="dynamic-delete-button text-danger"
                          />
                        </Popconfirm>
                      ) : null
                    }
                    onCancel={() => remove(field.name)}
                  />
                </Form.Item>
              </Form.Item>
            ))}
            <Form.Item className="mb-1">
              <Button
                type="primary"
                onClick={() => add()}
                icon={<PlusOutlined />}
                className="mt-1 mb-0 w-100"
              >
                Ajouter un profil
              </Button>

              <Form.ErrorList errors={errors} />
            </Form.Item>
          </>
        )}
      </Form.List>
    </>
  );
}
