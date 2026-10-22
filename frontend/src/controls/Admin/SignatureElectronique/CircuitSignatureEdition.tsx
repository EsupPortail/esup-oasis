/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import React from "react";
import { App, Form, Input, Modal } from "antd";
import { IComposante, QK_COMPOSANTES } from "@api";
import { useApi } from "@context/api/ApiProvider";
import { decisionEtab } from "@lib";

/** Saisie du circuit de signature d'une composante ; ses référents ne sont pas modifiés. */
export function CircuitSignatureEdition(props: {
  editedItem: IComposante;
  setEditedItem: (item: IComposante | undefined) => void;
}) {
  const [form] = Form.useForm<{ circuitSignature: string | null }>();
  const { message } = App.useApp();

  const mutationPatch = useApi().usePatch({
    path: `/composantes/{id}`,
    invalidationQueryKeys: [QK_COMPOSANTES],
    onSuccess: () => {
      message.success("Circuit de signature de la composante modifié").then();
      props.setEditedItem(undefined);
    },
  });

  return (
    <Modal
      open
      title={props.editedItem.libelle}
      okText="Enregistrer"
      cancelText="Annuler"
      confirmLoading={mutationPatch.isPending}
      onOk={form.submit}
      onCancel={() => props.setEditedItem(undefined)}
    >
      <Form
        form={form}
        layout="vertical"
        initialValues={{ circuitSignature: props.editedItem.circuitSignature }}
        onFinish={(values) =>
          mutationPatch.mutate({
            "@id": props.editedItem["@id"] as string,
            data: { circuitSignature: values.circuitSignature ?? null },
          })
        }
      >
        <Form.Item
          name="circuitSignature"
          label="Circuit de signature"
          extra={`Identifiant du circuit dans le parapheur. Laisser vide pour envoyer ${decisionEtab.defini} par e-mail.`}
        >
          <Input allowClear />
        </Form.Item>
      </Form>
    </Modal>
  );
}
