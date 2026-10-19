/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IComposante, QK_COMPOSANTES } from "@api";
import { Button, Card, Divider, Drawer, Flex, Form, Input, Popconfirm } from "antd";
import React from "react";
import { useApi } from "@context/api/ApiProvider";
import { DeleteOutlined, PlusOutlined } from "@ant-design/icons";
import UtilisateurFormItemSelect from "@controls/Forms/UtilisateurFormItemSelect";
import { RoleValues } from "@lib";

export function ComposanteEdition(props: {
  editedItem: IComposante;
  setEditedItem: (item: IComposante | undefined) => void;
}) {
  const [form] = Form.useForm();

  const mutationPatch = useApi().usePatch({
    path: `/composantes/{id}`,
    invalidationQueryKeys: [QK_COMPOSANTES],
    onSuccess: () => {
      props.setEditedItem(undefined);
    },
  });

  return (
    <Drawer
      className="bg-light-grey"
      open
      title="Éditer les référent•es de composante"
      onClose={() => props.setEditedItem(undefined)}
      size="large"
    >
      <Card
        actions={[
          <Button onClick={() => props.setEditedItem(undefined)}>Annuler</Button>,
          <Button type="primary" onClick={form.submit}>
            Enregistrer
          </Button>,
        ]}
      >
        <Form
          className="w-100"
          form={form}
          layout="vertical"
          onFinish={(values) => {
            mutationPatch.mutate({
              "@id": props.editedItem["@id"] as string,
              data: { ...values, libelle: undefined },
            });
            //     createOrUpdate(values);
          }}
          initialValues={props.editedItem}
        >
          <Form.Item name="libelle" label="Composante" rules={[{ required: true }]} required>
            <Input disabled />
          </Form.Item>

          <Divider>Référent•es</Divider>
          <Form.List name="referents">
            {(fields, { add, remove }, { errors }) => (
              <>
                {fields.map(({ key, ...field }) => (
                  <Form.Item className="mb-0" required key={key}>
                    <Flex justify="space-between" className="mb-1" gap={6}>
                      <Form.Item
                        {...field}
                        validateTrigger={["onChange", "onBlur"]}
                        rules={[
                          {
                            required: true,
                            whitespace: true,
                            message: "Un référent est nécessaire ou supprimez ce champ.",
                          },
                        ]}
                        noStyle
                      >
                        <UtilisateurFormItemSelect
                          placeholder="Rechercher un référent"
                          roleUtilisateur={RoleValues.ROLE_REFERENT_COMPOSANTE}
                        />
                      </Form.Item>
                      <Popconfirm title="Supprimer ce référent ?" onConfirm={() => remove(key)}>
                        <Button className="text-danger" icon={<DeleteOutlined />} />
                      </Popconfirm>
                    </Flex>
                  </Form.Item>
                ))}
                <Form.Item>
                  <Button
                    type="link"
                    onClick={() => {
                      add();
                    }}
                    icon={<PlusOutlined />}
                    className="p-0 mt-0 fs-09"
                  >
                    Ajouter un référent
                  </Button>

                  <Form.ErrorList errors={errors} />
                </Form.Item>
              </>
            )}
          </Form.List>
        </Form>
      </Card>
    </Drawer>
  );
}
