/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Button, Card, Divider, Drawer, Form, Input, Radio, Select, Switch } from "antd";
import { useApi } from "../../../../context/api/ApiProvider";
import React, { ReactElement, useEffect } from "react";
import { ITypeAmenagement } from "../../../../api/ApiTypeHelpers";
import { DOMAINES_AMENAGEMENTS_INFOS, getDomaineAmenagement } from "../../../../lib/amenagements";
import { PREFETCH_CATEGORIES_AMENAGEMENTS } from "../../../../api/ApiPrefetchHelpers";

interface AmenagementsEditionProps {
   editedItem?: ITypeAmenagement;
   setEditedItem: (item: ITypeAmenagement | undefined) => void;
}

interface IAmenagementForm {
   libelle: string;
   actif: boolean;
   categorie: string;
   domaine: string;
   decision: boolean;
}

/**
 * Handles the creation or update of an amenagement.
 *
 * @param {AmenagementsEditionProps} props - The component props.
 * @param {ITypeAmenagement} [props.editedItem] - The item being edited.
 * @param {function} props.setEditedItem - The function to set the edited item.
 * @returns {ReactElement} - The rendered component.
 */
export function TypeAmenagementEdition({
                                          editedItem,
                                          setEditedItem,
                                       }: AmenagementsEditionProps): ReactElement {
   const [form] = Form.useForm();

   const { data: categories } = useApi().useGetCollection(PREFETCH_CATEGORIES_AMENAGEMENTS);

   const mutationPost = useApi().usePost({
      path: "/types_amenagements",
      invalidationQueryKeys: ["/types_amenagements"],
      onSuccess: () => {
         setEditedItem(undefined);
      },
   });

   const mutationPatch = useApi().usePatch({
      path: `/types_amenagements/{id}`,
      invalidationQueryKeys: ["/types_amenagements"],
      onSuccess: () => {
         setEditedItem(undefined);
      },
   });

   function createOrUpdate(values: IAmenagementForm) {
      if (!editedItem) return;

      const data = {
         ...values,
         pedagogique: values.domaine === "pedagogique",
         examens: values.domaine === "examen",
         aideHumaine: values.domaine === "aideHumaine",
      };

      if (editedItem["@id"] === undefined) {
         // Création
         mutationPost?.mutate({ data });
      } else {
         // Modification
         mutationPatch?.mutate({
            "@id": editedItem["@id"],
            data,
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
            editedItem?.["@id"] ? "Éditer un type d'aménagement" : "Ajouter un type d'aménagement"
         }
         onClose={() => setEditedItem(undefined)}
         size="large"
      >
         <Card
            title="Type d'aménagement"
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
               initialValues={{
                  ...editedItem,
                  domaine: getDomaineAmenagement(editedItem)?.id,
               }}
            >
               <Form.Item
                  className="mb-2"
                  name="categorie"
                  label="Catégorie de l'aménagement"
                  rules={[{ required: true }]}
                  required
               >
                  <Select
                     options={(categories?.items || [])
                        .map((c) => ({
                           value: c["@id"],
                           label: c.libelle as string,
                        }))
                        .sort((a, b) => a.label.localeCompare(b.label))}
                  />
               </Form.Item>
               <Form.Item name="libelle" label="Libellé" rules={[{ required: true }]} required>
                  <Input autoFocus />
               </Form.Item>
               <Form.Item
                  name="libelleLong"
                  label="Libellé long"
                  help="Le libellé long est utilisé pour éditer les décisions d'établissement."
               >
                  <Input />
               </Form.Item>
               <Form.Item name="actif" label="Actif" className="mt-2" valuePropName="checked">
                  <Switch />
               </Form.Item>

               <Divider />

               <Form.Item
                  name="domaine"
                  label="Domaine de l'aménagement"
                  rules={[
                     {
                        required: true,
                        message: "Veuillez sélectionner un domaine.",
                     },
                  ]}
                  required
               >
                  <Radio.Group
                     className="ant-radio-vertical"
                     options={Object.keys(DOMAINES_AMENAGEMENTS_INFOS).map((o) => ({
                        value: o,
                        label: DOMAINES_AMENAGEMENTS_INFOS[o].singulier,
                     }))}
                  />
               </Form.Item>
               <Form.Item name="decision" label="Afficher dans la décision" className="mt-2" valuePropName="checked">
                  <Switch />
               </Form.Item>
            </Form>
         </Card>
      </Drawer>
   );
}
