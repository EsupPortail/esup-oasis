/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import {
  App,
  Button,
  Card,
  Col,
  DatePicker,
  Divider,
  Drawer,
  Form,
  Input,
  InputNumber,
  Row,
  Select,
} from "antd";
import React, { ReactElement, useEffect } from "react";
import { useApi } from "@context/api/ApiProvider";
import { ICampagneDemande, QK_TYPES_DEMANDES, QK_TYPES_DEMANDES_CAMPAGNES } from "@api";
import dayjs from "dayjs";
import { createDateAsUTC } from "@utils/dates";

interface CampagneEditionProps {
  editedItem?: ICampagneDemande;
  setEditedItem: (item: ICampagneDemande | undefined) => void;
  typeDemandeId: string;
}

/**
 * Handles the creation or update of a profile.
 *
 * @param {CampagneEditionProps} props - The component props.
 * @param {IProfil} [props.editedItem] - The item being edited.
 * @param {function} props.setEditedItem - The function to set the edited item.
 * @returns {ReactElement} - The rendered component.
 */
export function CampagneEdition({
  editedItem,
  setEditedItem,
  typeDemandeId,
}: CampagneEditionProps): ReactElement {
  const [form] = Form.useForm();
  const { notification } = App.useApp();

  const { data: commissions, isFetching: isFetchingCommissions } =
    useApi().useGetCollectionPaginated({
      path: "/commissions",
      page: 1,
      itemsPerPage: 100,
    });

  const mutationPost = useApi().usePost({
    path: "/types_demandes/{typeId}/campagnes",
    invalidationQueryKeys: [QK_TYPES_DEMANDES],
    url: `${typeDemandeId}/campagnes`,
    onSuccess: () => {
      setEditedItem(undefined);
    },
  });

  const mutationPatch = useApi().usePatch({
    path: `/types_demandes/{typeId}/campagnes/{id}`,
    invalidationQueryKeys: [QK_TYPES_DEMANDES, QK_TYPES_DEMANDES_CAMPAGNES],
    onSuccess: () => {
      setEditedItem(undefined);
    },
  });

  function createOrUpdate(values: ICampagneDemande) {
    if (!editedItem) return;

    // Test de la date d'archivage : elle doit être postérieure à la date de fin de campagne et à la date de la commission si elle est renseignée
    if (
      values.dateArchivage &&
      values.fin &&
      dayjs(values.dateArchivage).isBefore(dayjs(values.fin))
    ) {
      notification.error({
        title: "Erreur",
        description: "La date d'archivage doit être postérieure à la date de fin de campagne.",
      });
      return;
    }
    if (
      values.dateArchivage &&
      values.dateCommission &&
      dayjs(values.dateArchivage).isBefore(dayjs(values.dateCommission))
    ) {
      notification.error({
        title: "Erreur",
        description: "La date d'archivage doit être postérieure à la date de la commission.",
      });
      return;
    }

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

  if (!editedItem) return <></>;

  return (
    <Drawer
      className="bg-light-grey"
      open
      title={editedItem?.["@id"] ? "Éditer une campagne" : "Ajouter une campagne"}
      onClose={() => setEditedItem(undefined)}
      size="large"
    >
      <Card
        title="Campagne"
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
          <Row gutter={6}>
            <Col xs={24} sm={24} md={12}>
              <Form.Item
                name="debut"
                label="Début"
                rules={[{ required: true }]}
                getValueProps={(i) => ({ value: i ? dayjs(i) : undefined })}
                required
              >
                <DatePicker
                  className="w-100"
                  format="DD/MM/YYYY"
                  value={editedItem.debut ? dayjs(editedItem.debut) : null}
                  onChange={(date) => {
                    setEditedItem({
                      ...editedItem,
                      debut: date ? createDateAsUTC(date?.toDate()).toISOString() : undefined,
                    });
                  }}
                />
              </Form.Item>
            </Col>
            <Col xs={24} sm={24} md={12}>
              <Form.Item
                name="fin"
                label="Fin"
                rules={[{ required: true }]}
                getValueProps={(i) => ({ value: i ? dayjs(i) : undefined })}
                required
              >
                <DatePicker
                  className="w-100"
                  format="DD/MM/YYYY"
                  value={editedItem.fin ? dayjs(editedItem.fin) : null}
                  onChange={(date) => {
                    setEditedItem({
                      ...editedItem,
                      fin: date ? createDateAsUTC(date?.toDate()).toISOString() : undefined,
                    });
                  }}
                />
              </Form.Item>
            </Col>
          </Row>
          <Form.Item
            name="anneeCible"
            label="Année cible"
            help="Sert à déterminer les dates par défaut des profils des bénéficiaires. Si non renseignée, l'année de la date du jour est utilisée."
          >
            <InputNumber />
          </Form.Item>
          <Divider className="semi-bold mt-2">Commission</Divider>
          <Form.Item name="commission" label="Commission">
            <Select
              allowClear
              loading={isFetchingCommissions}
              options={commissions?.items.map((c) => ({
                label: c.libelle,
                value: c["@id"],
              }))}
              onChange={(value) => {
                setEditedItem({
                  ...editedItem,
                  commission: value,
                });
              }}
            />
          </Form.Item>
          <Form.Item
            name="dateCommission"
            label="Date de la commission"
            rules={[{ required: editedItem.commission !== undefined }]}
            getValueProps={(i) => ({ value: i ? dayjs(i) : undefined })}
            required={editedItem.commission !== undefined}
          >
            <DatePicker
              className="w-100"
              format="DD/MM/YYYY"
              value={editedItem.dateCommission ? dayjs(editedItem.dateCommission) : null}
              onChange={(date) => {
                setEditedItem({
                  ...editedItem,
                  dateCommission: date ? createDateAsUTC(date?.toDate()).toISOString() : null,
                });
              }}
            />
          </Form.Item>

          <Divider className="semi-bold mt-2">Archivage</Divider>
          <Form.Item
            name="dateArchivage"
            label="Date d'archivage"
            getValueProps={(i) => ({ value: i ? dayjs(i) : undefined })}
            help={
              <>
                Date à partir de laquelle les demandes "En cours" ou "Non-conforme" sont
                automatiquement passées à l'état "Refusée".
              </>
            }
          >
            <DatePicker
              className="w-100"
              format="DD/MM/YYYY"
              value={editedItem.dateArchivage ? dayjs(editedItem.dateArchivage) : null}
              onChange={(date) => {
                setEditedItem({
                  ...editedItem,
                  dateArchivage: date ? createDateAsUTC(date?.toDate()).toISOString() : null,
                });
              }}
            />
          </Form.Item>
        </Form>
      </Card>
    </Drawer>
  );
}
