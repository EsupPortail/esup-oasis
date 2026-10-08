/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { App, DatePicker, Form, Input, Modal } from "antd";
import { useEffect, useRef } from "react";
import dayjs, { Dayjs } from "dayjs";
import { useApi } from "@context/api/ApiProvider";
import { IAvisEse, QK_BENEFICIAIRES, QK_UTILISATEURS_DECISIONS, QK_UTILISATEURS_ITEM } from "@api";
import { isEnCoursSurPeriode } from "@utils/dates";
import { decisionEtab } from "@lib";

type ObservationsForm = {
  observations: string | null;
  dateAvisMedecin: Dayjs | null;
};

interface ModalDecisionObservationsProps {
  open: boolean;
  setOpen: (open: boolean) => void;
  decisionId: string;
  utilisateurId: string;
}

/**
 * Date suggérée en placeholder tant que la décision n'en porte pas : le début de l'avis santé en
 * cours, que le gestionnaire vérifie sur le document avant de la saisir.
 */
export function dateAvisMedecinSuggeree(
  dateAvisMedecin: string | null | undefined,
  avis: IAvisEse[],
): Dayjs | null {
  if (dateAvisMedecin) {
    return null;
  }

  const avisEnCours = avis.find((a) => isEnCoursSurPeriode(a.debut, a.fin));

  return avisEnCours?.debut ? dayjs(avisEnCours.debut) : null;
}

/**
 * Saisie par le gestionnaire des observations particulières et de la date de l'avis médical,
 * transmises au gabarit du document. Enregistrées tant que la décision n'est pas envoyée, sans
 * changer son état.
 */
export function ModalDecisionObservations({
  open,
  setOpen,
  decisionId,
  utilisateurId,
}: ModalDecisionObservationsProps) {
  const { message } = App.useApp();
  const [form] = Form.useForm<ObservationsForm>();

  const { data: decision, isFetching } = useApi().useGetItem({
    path: "/utilisateurs/{uid}/decisions/{annee}",
    url: decisionId,
    enabled: open && !!decisionId,
  });

  const { data: avis, isFetching: avisEnChargement } = useApi().useGetFullCollection({
    path: "/utilisateurs/{uid}/avis_ese",
    parameters: { uid: utilisateurId },
    query: { "order[debut]": "desc" },
    enabled: open && !!utilisateurId,
  });
  const suggestion = dateAvisMedecinSuggeree(decision?.dateAvisMedecin, avis?.items ?? []);

  const mutateDecision = useApi().usePatch({
    path: "/utilisateurs/{uid}/decisions/{annee}",
    invalidationQueryKeys: [
      QK_BENEFICIAIRES,
      QK_UTILISATEURS_ITEM,
      QK_UTILISATEURS_DECISIONS,
      utilisateurId,
    ],
    onSuccess: () => {
      message.success("Saisie enregistrée").then();
      setOpen(false);
    },
    onError: () => {
      message.error("Erreur lors de l'enregistrement").then();
    },
  });

  // valeurs posées une fois par ouverture : une relecture des données ne doit pas écraser la saisie en cours
  const initialisee = useRef(false);
  useEffect(() => {
    if (!open) {
      initialisee.current = false;
      return;
    }
    if (decision && !avisEnChargement && !initialisee.current) {
      form.setFieldsValue({
        observations: decision.observations ?? "",
        dateAvisMedecin: decision.dateAvisMedecin ? dayjs(decision.dateAvisMedecin) : null,
      });
      initialisee.current = true;
    }
  }, [open, decision, avisEnChargement, form]);

  function handleSubmit(values: ObservationsForm) {
    const observations = values.observations?.trim() ? values.observations.trim() : null;
    const dateAvisMedecin = values.dateAvisMedecin
      ? values.dateAvisMedecin.format("YYYY-MM-DD")
      : null;
    mutateDecision.mutate({
      "@id": decisionId,
      data: { observations, dateAvisMedecin },
    });
  }

  return (
    <Modal
      open={open}
      onCancel={() => setOpen(false)}
      onOk={() => form.submit()}
      okText="Enregistrer"
      cancelText="Annuler"
      confirmLoading={mutateDecision.isPending}
      okButtonProps={{ disabled: !decision || isFetching || avisEnChargement }}
      title="Date de l'avis médical et observations particulières"
      width={640}
    >
      <Form<ObservationsForm>
        layout="vertical"
        form={form}
        onFinish={handleSubmit}
        initialValues={{ observations: "", dateAvisMedecin: null }}
      >
        {/* le compteur de caractères occupe la ligne sous le champ : pas de légende ici */}
        <Form.Item name="observations" label="Observations particulières">
          <Input.TextArea
            rows={4}
            maxLength={4000}
            showCount
            disabled={isFetching}
            placeholder={`Texte libre, transmis au gabarit ${decisionEtab.de}`}
          />
        </Form.Item>
        <Form.Item
          name="dateAvisMedecin"
          label="Date de l'avis médical"
          extra={
            suggestion
              ? "Date suggérée : le début de l'avis santé en cours, à vérifier sur le document avant de la saisir."
              : `Date à laquelle le médecin a rendu son avis, transmise au gabarit ${decisionEtab.de}.`
          }
        >
          <DatePicker
            className="w-100"
            picker="date"
            format="DD/MM/YYYY"
            allowClear
            disabled={isFetching}
            placeholder={suggestion ? suggestion.format("DD/MM/YYYY") : "JJ/MM/AAAA"}
            defaultPickerValue={suggestion ?? undefined}
          />
        </Form.Item>
      </Form>
    </Modal>
  );
}
