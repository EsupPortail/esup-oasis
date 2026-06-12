/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import {
  IAmenagement,
  ICategorieAmenagement,
  PREFETCH_TYPES_AMENAGEMENTS,
  QK_AMENAGEMENTS,
  QK_UTILISATEURS_AMENAGEMENTS,
} from "@api";
import { Button, Checkbox, Col, DatePicker, Divider, Flex, Form, Modal, Row, Space } from "antd";
import { useApi } from "@context/api/ApiProvider";
import React, { useEffect } from "react";
import dayjs, { Dayjs } from "dayjs";
import { useAuth } from "@/auth/AuthProvider";
import Spinner from "@controls/Spinner/Spinner";

type IAmenagementForm = IAmenagement & {
  types: string[];
  dateDebut: Dayjs | null;
  dateFin: Dayjs | null;
};

export function ModalCategorieAddAmenagement(props: {
  open: boolean;
  setOpen: (open: boolean) => void;
  categorieAmenagementAjoute?: ICategorieAmenagement;
  utilisateurId?: string;
}) {
  const user = useAuth().user;
  const [form] = Form.useForm<IAmenagementForm>();
  const { data: typesAmenagements } = useApi().useGetFullCollection(PREFETCH_TYPES_AMENAGEMENTS);
  const [submitted, setSubmitted] = React.useState<boolean>(false);
  const [amenagementsACreer, setAmenagementsACreer] = React.useState<IAmenagement[]>([]);

  const mutatePostAmenagement = useApi().usePost({
    path: "/utilisateurs/{uid}/amenagements",
    url: `${props.utilisateurId}/amenagements`,
    onSuccess: () => {
      setAmenagementsACreer((prev) => prev.slice(1));
    },
    onError: () => {
      setSubmitted(false);
    },
    invalidationQueryKeys: [QK_UTILISATEURS_AMENAGEMENTS, QK_AMENAGEMENTS],
  });

  useEffect(() => {
    if (!submitted) return;
    if (amenagementsACreer.length === 0) {
      // C'est fini, on sort
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setSubmitted(false);
      props.setOpen(false);
    } else {
      // On crée un amenagement
      const amenagement = amenagementsACreer[0];
      if (amenagement) {
        mutatePostAmenagement.mutate({
          data: amenagement,
        });
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [amenagementsACreer, submitted]);

  function handleSubmit(values: IAmenagementForm) {
    setSubmitted(true);
    setAmenagementsACreer(
      values.types.map((type) => ({
        typeAmenagement: type,
        semestre1: values.semestre1,
        semestre2: values.semestre2,
        debut: values.dateDebut ? values.dateDebut.format("YYYY-MM-DD") : null,
        fin: values.dateFin ? values.dateFin.format("YYYY-MM-DD") : null,
        commentaire: null,
        suivi: null,
      })) as IAmenagement[],
    );
  }

  useEffect(() => {
    if (
      typesAmenagements &&
      typesAmenagements.items &&
      typesAmenagements.items.filter(
        (ta) => ta.actif && ta.categorie === props.categorieAmenagementAjoute?.["@id"],
      ).length === 1
    ) {
      form.setFieldValue("types", [
        typesAmenagements.items.filter(
          (ta) => ta.actif && ta.categorie === props.categorieAmenagementAjoute?.["@id"],
        )[0]["@id"],
      ]);
    }
  }, [form, props.categorieAmenagementAjoute, typesAmenagements]);

  return (
    <Modal
      open={props.open}
      onCancel={() => props.setOpen(false)}
      onOk={() => {
        form.submit();
      }}
      title="Ajouter des aménagements"
      width={700}
      footer={
        <Flex justify="space-between" align="center">
          <Space className={submitted ? "" : "v-hidden"}>
            <Spinner />
            <span>Création des aménagements en cours...</span>
          </Space>
          <Space>
            <Button
              onClick={() => {
                props.setOpen(false);
              }}
            >
              Annuler
            </Button>
            <Button
              loading={submitted}
              type="primary"
              onClick={() => {
                form.submit();
              }}
            >
              Ajouter
            </Button>
          </Space>
        </Flex>
      }
    >
      <p>
        Vous pouvez ajouter ici un ou plusieurs aménagements de la catégorie{" "}
        <strong className="semi-bold">{props.categorieAmenagementAjoute?.libelle}</strong>.
      </p>
      <Form<IAmenagementForm>
        layout="vertical"
        form={form}
        onFinish={handleSubmit}
        initialValues={{
          types: [],
          dateDebut: dayjs(),
        }}
      >
        <Form.Item
          name="types"
          label={<div className="semi-bold mb-1">Types d'aménagement à ajouter :</div>}
          required
          rules={[
            {
              required: true,
              message: "Veuillez sélectionner au moins un type d'aménagement",
            },
          ]}
        >
          <Checkbox.Group
            className="checkbox-group-vertical"
            options={(typesAmenagements?.items || [])
              .filter(
                (ta) => ta.actif && ta.categorie === props.categorieAmenagementAjoute?.["@id"],
              )
              .map((ta) => ({ label: ta.libelle, value: ta["@id"] as string }))}
          />
        </Form.Item>

        <Divider />
        <p>
          Les informations ci-dessous seront communes aux aménagements qui seront ajoutés. Penserez
          à les éditer ensuite si besoin.
        </p>

        <Row gutter={[16, 8]} className="mb-2">
          <Col xs={24} sm={24} md={12}>
            <div className="semi-bold">Semestres</div>
            <Row>
              <Col span={12}>
                <Form.Item name="semestre1" valuePropName="checked">
                  <Checkbox disabled={!user?.isGestionnaire}>Semestre 1</Checkbox>
                </Form.Item>
              </Col>
              <Col span={12}>
                <Form.Item name="semestre2" valuePropName="checked">
                  <Checkbox disabled={!user?.isGestionnaire}>Semestre 2</Checkbox>
                </Form.Item>
              </Col>
            </Row>
          </Col>
          <Col xs={24} sm={24} md={12}>
            <div className="semi-bold">Période</div>
            <Row>
              <Col span={12}>
                <Form.Item
                  name="dateDebut"
                  label="Date début"
                  rules={[
                    {
                      validator: async (_, value) => {
                        if (
                          !value &&
                          !form.getFieldValue("semestre1") &&
                          !form.getFieldValue("semestre2")
                        ) {
                          return Promise.reject(
                            "Une date de début ou un semestre doit être renseigné",
                          );
                        }
                      },
                    },
                  ]}
                >
                  <DatePicker
                    disabled={!user?.isGestionnaire}
                    className="text-center"
                    picker="date"
                    format="DD/MM/YYYY"
                  />
                </Form.Item>
              </Col>

              <Col span={12}>
                <Form.Item
                  name="dateFin"
                  label="Date fin"
                  rules={[
                    {
                      validator: async (_, value) => {
                        if (
                          value &&
                          form.getFieldValue("dateDebut") &&
                          value.isBefore(form.getFieldValue("dateDebut"))
                        ) {
                          return Promise.reject(
                            "La date de fin doit être postérieure à la date de début",
                          );
                        }
                      },
                    },
                  ]}
                >
                  <DatePicker
                    disabled={!user?.isGestionnaire}
                    className="text-center"
                    picker="date"
                    format="DD/MM/YYYY"
                  />
                </Form.Item>
              </Col>
            </Row>
          </Col>
        </Row>
      </Form>
    </Modal>
  );
}
