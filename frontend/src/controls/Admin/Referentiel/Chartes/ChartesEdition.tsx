/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Button, Card, Drawer, Form, Input, Select } from "antd";
import { useApi } from "@context/api/ApiProvider";
import React, { lazy, ReactElement, Suspense, useEffect } from "react";
import { ICharte, PREFETCH_PROFILS, QK_CHARTES } from "@api";
import { BENEFICIAIRE_PROFIL_A_DETERMINER } from "@/constants";

const HtmlEditor = lazy(() => import("@controls/Forms/HtmlEditor"));

interface ChartesEditionProps {
  editedItem?: ICharte;
  setEditedItem: (item: ICharte | undefined) => void;
}

/**
 * Handles the creation or update of a clubSportife.
 *
 * @param {ChartesEditionProps} props - The component props.
 * @param {ICharte} [props.editedItem] - The item being edited.
 * @param {function} props.setEditedItem - The function to set the edited item.
 * @returns {ReactElement} - The rendered component.
 */
export function ChartesEdition({ editedItem, setEditedItem }: ChartesEditionProps): ReactElement {
  const [form] = Form.useForm();
  const { data: profils, isFetching } = useApi().useGetFullCollection(PREFETCH_PROFILS);
  const [contenu, setContenu] = React.useState<string>(editedItem?.contenu || "");

  const mutationPost = useApi().usePost({
    path: "/chartes",
    invalidationQueryKeys: [QK_CHARTES],
    onSuccess: () => {
      setEditedItem(undefined);
    },
  });

  const mutationPatch = useApi().usePatch({
    path: `/chartes/{id}`,
    invalidationQueryKeys: [QK_CHARTES],
    onSuccess: () => {
      setEditedItem(undefined);
    },
  });

  function createOrUpdate(values: ICharte) {
    if (!editedItem) return;

    const data = {
      ...values,
      contenu: contenu,
    };

    if (editedItem["@id"] === undefined) {
      // Création
      mutationPost?.mutate({
        data: data,
      });
    } else {
      // Modification
      mutationPatch?.mutate({
        "@id": editedItem["@id"],
        data: data,
      });
    }
  }

  // Synchronisation editedItem / form
  useEffect(() => {
    if (editedItem) {
      form.setFieldsValue(editedItem);
    }
  }, [editedItem, form]);

  useEffect(() => {
    if (editedItem && editedItem["@id"]) {
      form.setFieldsValue(editedItem);
    }
  }, [editedItem, form]);

  return (
    <Drawer
      className="bg-light-grey"
      open
      title={editedItem?.["@id"] ? "Éditer une charte" : "Ajouter une charte"}
      onClose={() => setEditedItem(undefined)}
      mask={{ closable: false }}
      size="large"
    >
      <Card
        title="Charte"
        actions={[
          <Button onClick={() => setEditedItem(undefined)}>Annuler</Button>,
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
            createOrUpdate(values);
          }}
          initialValues={editedItem}
        >
          <Form.Item name="libelle" label="Libellé" rules={[{ required: true }]} required>
            <Input autoFocus />
          </Form.Item>
          <Form.Item
            name="profilsAssocies"
            label="Profils associés"
            required
            rules={[
              {
                required: true,
                message: "Veuillez sélectionner un ou plusieurs profils associés",
              },
              {
                type: "array",
                min: 1,
                message: "Veuillez sélectionner un ou plusieurs profils associés",
              },
            ]}
          >
            <Select
              loading={isFetching}
              options={profils?.items
                .filter((p) => p["@id"] !== BENEFICIAIRE_PROFIL_A_DETERMINER && p.actif)
                .map((p) => ({ label: p.libelle, value: p["@id"] }))}
              mode="tags"
            />
          </Form.Item>
        </Form>
        <h3>Contenu de la charte</h3>
        <Suspense fallback={<span>Chargement de l'éditeur...</span>}>
          <HtmlEditor
            value={contenu}
            onChange={(c) => setContenu(c)}
            ariaLabel="Contenu de la charte"
          />
        </Suspense>
      </Card>
    </Drawer>
  );
}
