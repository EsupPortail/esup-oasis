/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useMemo } from "react";
import { Alert, Form, FormInstance, InputNumber, Select } from "antd";
import { ExclamationOutlined } from "@ant-design/icons";
import dayjs from "dayjs";
import { useApi } from "@context/api/ApiProvider";
import { useAuth } from "@/auth/AuthProvider";
import { RoleValues } from "@lib";
import { PeriodeRhItem } from "@controls/Items/PeriodeRhItem";
import { TypeEvenementItem } from "@controls/Items/TypeEvenementItem";
import UtilisateurFormItemSelect from "@controls/Forms/UtilisateurFormItemSelect";
import { TabEvenementParticipantsBeneficiaires } from "@controls/TabsContent/SubTabs/TabEvenementParticipantsBeneficiaires";
import { InterventionsForfaitMetadata } from "@controls/Interventions/InterventionsForfaitMetadata";
import { IInterventionForfait } from "@api";
import { UseStateDispatch } from "@utils/utils";

interface InterventionsForfaitFormProps {
  form: FormInstance<IInterventionForfait>;
  editedItem: Partial<IInterventionForfait>;
  setEditedItem: UseStateDispatch<Partial<IInterventionForfait | undefined>>;
  onFinish: (values: IInterventionForfait) => void;
  isEditable: boolean;
  setBeneficiairesModifies: (value: boolean) => void;
}

export const InterventionsForfaitForm: React.FC<InterventionsForfaitFormProps> = ({
  form,
  editedItem,
  setEditedItem,
  onFinish,
  isEditable,
  setBeneficiairesModifies,
}) => {
  const user = useAuth().user;
  const { data: periodes, isFetching: isFetchingPeriodes } = useApi().useGetFullCollection({
    path: "/periodes",
  });
  const { data: typesEvenements, isFetching: isFetchingTypesEvenements } =
    useApi().useGetFullCollection({
      path: "/types_evenements",
      query: {
        forfait: true,
      },
    });

  const periodeOptions = useMemo(
    () =>
      periodes?.items
        .filter((p) => !p.envoyee || !isEditable)
        .filter((p) => user?.isAdmin || dayjs(p.butoir).endOf("day").isAfter(dayjs()))
        .map((periode) => ({
          key: periode["@id"],
          value: periode["@id"],
          label: <PeriodeRhItem periode={periode} />,
        })) ?? [],
    [periodes, isEditable, user?.isAdmin],
  );

  const typeEvenementOptions = useMemo(
    () =>
      typesEvenements?.items.map((typeEvenement) => ({
        key: typeEvenement["@id"],
        value: typeEvenement["@id"],
        label: <TypeEvenementItem typeEvenement={typeEvenement} showAvatar={false} />,
      })) ?? [],
    [typesEvenements],
  );

  return (
    <Form<IInterventionForfait>
      layout="vertical"
      form={form}
      onFinish={onFinish}
      initialValues={{
        type: typesEvenements?.items[0]?.["@id"],
      }}
    >
      <Form.Item
        label="Période"
        name="periode"
        required
        rules={[{ required: true, message: "Veuillez sélectionner une période" }]}
      >
        <Select
          value={editedItem.periode}
          onChange={(value) => {
            setEditedItem({ ...editedItem, periode: value });
          }}
          className="w-100"
          loading={isFetchingPeriodes}
          allowClear
          disabled={!isEditable}
          options={periodeOptions}
        />
      </Form.Item>
      <Form.Item
        label="Intervenant"
        name="intervenant"
        rules={[{ required: true, message: "Veuillez sélectionner un intervenant" }]}
        required
      >
        <UtilisateurFormItemSelect
          className="w-100"
          onSelect={(value) => {
            setEditedItem({ ...editedItem, intervenant: value });
          }}
          placeholder="Rechercher un intervenant"
          roleUtilisateur={RoleValues.ROLE_INTERVENANT}
          disabled={!isEditable}
        />
      </Form.Item>
      <Form.Item
        label="Catégorie d'événement"
        required
        name="type"
        rules={[
          {
            required: true,
            message: "Veuillez sélectionner une catégorie d'événement",
          },
        ]}
      >
        <Select
          value={editedItem.type}
          onChange={(value) => {
            setEditedItem({ ...editedItem, type: value });
          }}
          className="w-100"
          loading={isFetchingTypesEvenements}
          allowClear
          disabled={!isEditable}
          options={typeEvenementOptions}
        />
      </Form.Item>
      <Form.Item
        label="Durée"
        rules={[{ required: true, message: "Veuillez saisir une durée" }]}
        required
        name="heures"
      >
        <InputNumber
          value={editedItem.heures}
          onChange={(value) => {
            setEditedItem({ ...editedItem, heures: value || "0" });
          }}
          className="w-100"
          placeholder="Durée en heures"
          min="0.00"
          step={0.5}
          precision={1}
          decimalSeparator=","
          disabled={!isEditable}
        />
      </Form.Item>
      <TabEvenementParticipantsBeneficiaires
        evenement={editedItem}
        setEvenement={(v) => {
          setEditedItem({ ...editedItem, ...(v as IInterventionForfait) });
          setBeneficiairesModifies(true);
        }}
      />
      {(editedItem.beneficiaires || []).length === 0 && (
        <Alert
          type="warning"
          showIcon
          icon={<ExclamationOutlined />}
          title="Aucun bénéficiaire associé"
          description="Vous devez associer les bénéficiaires de cette intervention."
        />
      )}
      <InterventionsForfaitMetadata editedItem={editedItem} />
    </Form>
  );
};
