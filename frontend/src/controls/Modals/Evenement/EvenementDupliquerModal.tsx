/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useState } from "react";
import { App, Button, Form, Modal } from "antd";
import { QuestionCircleOutlined } from "@ant-design/icons";
import { Evenement } from "@lib";
import { useApi } from "@context/api/ApiProvider";
import { queryClient } from "@/queryClient";
import { arrayContainsDuplicates } from "@utils/array";
import { createDateAsUTC } from "@utils/dates";
import { TYPE_EVENEMENT_RENFORT } from "@/constants";
import { IEvenement, QK_EVENEMENTS, QK_STATISTIQUES_EVENEMENTS } from "@api";
import { UseStateDispatch } from "@utils/utils";
import {
  EvenementDupliquerForm,
  IDuplicationOptions,
} from "@controls/Modals/Evenement/EvenementDupliquerForm";
import dayjs, { Dayjs } from "dayjs";

interface IEvenementDupliquerDrawer {
  evenement: Evenement;
  open: boolean;
  setOpen: UseStateDispatch<boolean>;
}

/**
 * Modale de duplication d'un évènement sur plusieurs jours.
 */
export default function EvenementDupliquerModal({
  evenement,
  open,
  setOpen,
}: IEvenementDupliquerDrawer): ReactElement {
  const [form] = Form.useForm();
  const { notification } = App.useApp();
  const [afficherAide, setAfficherAide] = useState(false);
  const [datesSelectionnees, setDatesSelectionnees] = useState<Dayjs[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const postEvenement = useApi().usePost({
    path: "/evenements",
  });

  const handleClose = () => {
    setOpen(false);
  };

  async function handleSubmit(values: IDuplicationOptions) {
    if (
      arrayContainsDuplicates(datesSelectionnees.map((d) => d.format("YYYY-MM-DD"))) &&
      (values.intervenant || values.beneficiaire)
    ) {
      notification.error({
        title: "Duplication impossible",
        description:
          "Vous ne pouvez pas dupliquer un évènement sur le même jour avec un intervenant ou un bénéficiaire. Un utilisateur ne peut avoir 2 évènements sur le même créneau horaire.",
      });
      return;
    }

    setIsSubmitting(true);
    try {
      const datesToProcess = [...datesSelectionnees];
      while (datesToProcess.length > 0) {
        const date = datesToProcess[0];
        const debut = dayjs(date)
          .hour(evenement.debutDate()?.getHours() || 0)
          .minute(evenement.debutDate()?.getMinutes() || 0)
          .second(0)
          .millisecond(0);

        const fin = dayjs(date)
          .hour(evenement.finDate()?.getHours() || 0)
          .minute(evenement.finDate()?.getMinutes() || 0)
          .second(0)
          .millisecond(0);

        const nvoEvenement: IEvenement = {
          debut: createDateAsUTC(debut.toDate()).toISOString(),
          fin: createDateAsUTC(fin.toDate()).toISOString(),
          libelle: evenement.libelle,
          campus: evenement.campus,
          type: evenement.type,
          beneficiaires: values.beneficiaire ? evenement.beneficiaires : undefined,
          intervenant: values.intervenant ? evenement.intervenant : undefined,
          suppleants: values.suppleants ? evenement.suppleants : undefined,
          salle: values.salle ? evenement.salle : undefined,
          equipements: values.equipements ? evenement.equipements : undefined,
          tempsPreparation: values.paiement ? evenement.tempsPreparation : undefined,
          tempsSupplementaire: values.paiement ? evenement.tempsSupplementaire : undefined,
        } as IEvenement;

        await postEvenement.mutateAsync({
          data: nvoEvenement,
        });

        datesToProcess.shift();
        setDatesSelectionnees([...datesToProcess]);
      }

      notification.success({ title: "Évènement dupliqué avec succès" });
      await queryClient.invalidateQueries({ queryKey: [QK_EVENEMENTS] });
      await queryClient.invalidateQueries({ queryKey: [QK_STATISTIQUES_EVENEMENTS] });

      handleClose();
    } catch {
      notification.error({
        title: "Erreur lors de la duplication",
        description: "Certains évènements n'ont pas pu être créés.",
      });
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <Modal
      destroyOnHidden
      title={
        <>
          {!afficherAide && (
            <Button
              className="float-right mr-5 p-0"
              icon={<QuestionCircleOutlined />}
              onClick={() => setAfficherAide(true)}
              type="link"
              style={{ marginTop: -7 }}
            >
              Aide
            </Button>
          )}
          {"Dupliquer un évènement".toLocaleUpperCase()}
        </>
      }
      onOk={() => form.submit()}
      onCancel={handleClose}
      okButtonProps={{
        disabled: datesSelectionnees.length === 0,
      }}
      okText="Dupliquer"
      open={open}
      className="oasis-drawer"
      width={800}
      confirmLoading={isSubmitting}
    >
      <EvenementDupliquerForm
        form={form}
        evenement={evenement}
        datesSelectionnees={datesSelectionnees}
        setDatesSelectionnees={setDatesSelectionnees}
        options={{
          horaire: true,
          typeEvenement: true,
          beneficiaire: true,
          intervenant: evenement.type === TYPE_EVENEMENT_RENFORT,
          suppleants: false,
          campus: true,
          salle: false,
          equipements: true,
          paiement: false,
        }}
        onFinish={handleSubmit}
        afficherAide={afficherAide}
        setAfficherAide={setAfficherAide}
      />
    </Modal>
  );
}
