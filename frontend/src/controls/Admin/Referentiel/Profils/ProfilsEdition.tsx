/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Button, Card, Drawer, Form, Input, Switch } from "antd";
import { useApi } from "@context/api/ApiProvider";
import React, { ReactElement, useEffect } from "react";
import { IProfil, QK_PROFILS } from "@api";
import { decisionEtab } from "@lib";

interface ProfilsEditionProps {
  editedItem?: IProfil;
  setEditedItem: (item: IProfil | undefined) => void;
}

/**
 * Handles the creation or update of a profile.
 *
 * @param {ProfilsEditionProps} props - The component props.
 * @param {IProfil} [props.editedItem] - The item being edited.
 * @param {function} props.setEditedItem - The function to set the edited item.
 * @returns {ReactElement} - The rendered component.
 */
export function ProfilsEdition({ editedItem, setEditedItem }: ProfilsEditionProps): ReactElement {
  const [form] = Form.useForm();
  // un avis médical n'a de sens que pour un profil de handicap
  const avecTypologie = Form.useWatch("avecTypologie", form);

  const mutationPost = useApi().usePost({
    path: "/profils",
    invalidationQueryKeys: [QK_PROFILS],
    onSuccess: () => {
      setEditedItem(undefined);
    },
  });

  const mutationPatch = useApi().usePatch({
    path: `/profils/{id}`,
    invalidationQueryKeys: [QK_PROFILS],
    onSuccess: () => {
      setEditedItem(undefined);
    },
  });

  function createOrUpdate(saisie: IProfil) {
    if (!editedItem) return;
    // option masquée hors profil de handicap : elle ne doit pas y rester cochée
    const values = {
      ...saisie,
      avisMedicalRequis: !!saisie.avecTypologie && !!saisie.avisMedicalRequis,
    };

    if (editedItem["@id"] === undefined) {
      // Création
      mutationPost?.mutate({
        data: values,
      });
    } else {
      // Modification
      mutationPatch?.mutate({
        "@id": editedItem["@id"],
        data: values,
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
      title={
        editedItem?.["@id"]
          ? "Éditer un élément du référentiel"
          : "Ajouter un élément au référentiel"
      }
      onClose={() => setEditedItem(undefined)}
      size="large"
    >
      <Card
        title="Catégorie d'évènement"
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
          <Form.Item name="actif" label="Actif" valuePropName="checked">
            <Switch />
          </Form.Item>
          <Form.Item
            name="avecTypologie"
            label="Typologie de handicap à associer"
            valuePropName="checked"
          >
            <Switch />
          </Form.Item>
          {avecTypologie && (
            <Form.Item
              name="avisMedicalRequis"
              label={`Avis médical requis pour éditer ${decisionEtab.defini}`}
              tooltip={`La date de l'avis du médecin devra être renseignée avant l'édition ${decisionEtab.de} des bénéficiaires de ce profil.`}
              valuePropName="checked"
            >
              <Switch />
            </Form.Item>
          )}
        </Form>
      </Card>
    </Drawer>
  );
}
