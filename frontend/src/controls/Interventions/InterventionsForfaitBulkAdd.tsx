/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useEffect, useMemo, useState } from "react";
import "@routes/administration/Administration.scss";
import { App, Button, Card, Col, Drawer, Form, InputNumber, Row, Select, Space } from "antd";
import { MinusCircleOutlined, PlusOutlined, SaveOutlined } from "@ant-design/icons";
import { RoleValues } from "@lib";
import UtilisateurFormItemSelect from "@controls/Forms/UtilisateurFormItemSelect";
import { CategorieSelectWithAvatar } from "@controls/Forms/CategorieSelectWithAvatar";
import { useApi } from "@context/api/ApiProvider";
import { IInterventionForfait, QK_INTERVENTIONS_FORFAIT } from "@api";
import { PeriodeRhItem } from "@controls/Items/PeriodeRhItem";
import dayjs from "dayjs";
import { useAuth } from "@/auth/AuthProvider";

interface InterventionsForfaitBulkAddProps {
  onClose: () => void;
}

declare type InterventionsForfaitBulkInformations = {
  intervenant?: string;
  type?: string;
  repartitions?: {
    periode: string;
    heures: number;
  }[];
};

/**
 * Add multiple forfait interventions.
 * @param {InterventionsForfaitBulkAddProps} props - The props for the method.
 * @param {function} props.onClose - The function to close the component.
 * @returns {ReactElement} - The component.
 */
export default function InterventionsForfaitBulkAdd({
  onClose,
}: InterventionsForfaitBulkAddProps): ReactElement {
  const { message } = App.useApp();
  const user = useAuth().user;
  const [submitted, setSubmitted] = useState(false);
  const [bulkInformations, setBulkInformations] = useState<InterventionsForfaitBulkInformations>();
  const [form] = Form.useForm<InterventionsForfaitBulkInformations>();
  const { data: periodes, isFetching: isFetchingPeriodes } = useApi().useGetFullCollection({
    path: "/periodes",
  });

  // --- Mutations
  const createInterventionForfait = useApi().usePost({
    path: "/interventions_forfait",
    invalidationQueryKeys: [QK_INTERVENTIONS_FORFAIT],
    onSuccess: () => {
      window.setTimeout(() => {
        // Remove first element of datesSelectionnees
        setBulkInformations((prev) => {
          if (prev?.repartitions) {
            prev.repartitions.shift();
          }
          return { ...prev };
        });
      }, 500);
    },
  });

  function isValideSubmit(data: InterventionsForfaitBulkInformations) {
    return data.intervenant && data.type && data.repartitions && data.repartitions.length > 0;
  }

  function postIntervention() {
    if (bulkInformations?.repartitions && bulkInformations.repartitions.length > 0) {
      const repartition = bulkInformations.repartitions[0];
      const data = {
        type: bulkInformations.type,
        intervenant: bulkInformations.intervenant,
        periode: repartition.periode,
        heures: repartition.heures.toString(),
      } as IInterventionForfait;

      createInterventionForfait.mutate({
        data,
      });
    } else {
      message.success("Interventions au forfait créées avec succès").then();

      setSubmitted(false);
      onClose();
    }
  }

  useEffect(() => {
    if (submitted) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      postIntervention();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [submitted, bulkInformations]);

  const periodeOptions = useMemo(
    () =>
      periodes?.items
        .filter((p) => !p.envoyee)
        .filter((p) => user?.isAdmin || dayjs(p.butoir).endOf("day").isAfter(dayjs()))
        .map((item) => ({
          key: item["@id"] as string,
          value: item["@id"] as string,
          label: <PeriodeRhItem periode={item} />,
        })) ?? [],
    [periodes, user],
  );

  return (
    <Drawer
      title="Ajouter plusieurs interventions au forfait"
      open
      onClose={onClose}
      size="large"
      className="bg-light-grey"
    >
      <Form
        form={form}
        layout="vertical"
        onFinish={(values) => {
          if (!isValideSubmit(values)) {
            message.error("Veuillez compléter les informations des périodes").then();
            return;
          }

          setSubmitted(() => {
            setBulkInformations(values);
            return true;
          });
        }}
        onValuesChange={(_changedValues, allValues) => {
          setBulkInformations(allValues);
        }}
      >
        <Card title="Informations communes">
          <Form.Item
            name="intervenant"
            label="Intervenant"
            required
            rules={[
              {
                required: true,
                message: "Intervenant requis",
              },
            ]}
          >
            <UtilisateurFormItemSelect
              onSelect={(value) => {
                form.setFieldValue("intervenant", value);
                setBulkInformations({
                  ...bulkInformations,
                  intervenant: value,
                });
              }}
              placeholder="Rechercher un intervenant"
              roleUtilisateur={RoleValues.ROLE_INTERVENANT}
            />
          </Form.Item>
          <CategorieSelectWithAvatar
            typeSelectionne={bulkInformations?.type}
            setTypeSelectionne={(value) => {
              form.setFieldValue("type", value);
              setBulkInformations({
                ...bulkInformations,
                type: value,
              });
            }}
            forfait
          />
        </Card>
        <Card
          title="Répartition des heures sur les périodes"
          className="mt-3"
          actions={[
            <div className="w-100">
              <Space className="float-right mr-3">
                <span>Total</span>
                <span className="semi-bold">
                  {bulkInformations?.repartitions?.reduce(
                    (acc, cur) => acc + Number(cur?.heures || 0),
                    0,
                  ) || 0}{" "}
                  h
                </span>
              </Space>
            </div>,
          ]}
        >
          <Form.List name="repartitions">
            {(fields, { add, remove }) => (
              <>
                {fields.map(({ key, ...field }) => (
                  <Row key={key} gutter={[16, 16]}>
                    <Form.Item
                      noStyle
                      shouldUpdate={(prevValues, curValues) =>
                        prevValues.repartitions !== curValues.repartitions
                      }
                    >
                      {() => (
                        <Col span={12}>
                          <Form.Item
                            {...field}
                            label="Période"
                            name={[field.name, "periode"]}
                            rules={[
                              {
                                required: true,
                                message: "Période requise",
                              },
                            ]}
                          >
                            <Select loading={isFetchingPeriodes} options={periodeOptions} />
                          </Form.Item>
                        </Col>
                      )}
                    </Form.Item>
                    <Col span={10}>
                      <Form.Item
                        {...field}
                        label="Nombre d'heures"
                        name={[field.name, "heures"]}
                        rules={[
                          {
                            required: true,
                            message: "Nombre d'heures requis",
                          },
                        ]}
                      >
                        <InputNumber precision={2} decimalSeparator="," />
                      </Form.Item>
                    </Col>
                    <Col span={2} className="d-flex-center">
                      <MinusCircleOutlined className="mt-2" onClick={() => remove(field.name)} />
                    </Col>
                  </Row>
                ))}

                <Form.Item>
                  <Button type="dashed" onClick={() => add()} block icon={<PlusOutlined />}>
                    Ajouter une intervention
                  </Button>
                </Form.Item>
              </>
            )}
          </Form.List>
        </Card>
        <Space className="mt-3 w-100 d-flex-center">
          <Button onClick={onClose}>Annuler</Button>
          <Button
            type="primary"
            htmlType="submit"
            icon={<SaveOutlined />}
            loading={submitted}
            disabled={
              submitted ||
              form.getFieldValue("intervenant") === undefined ||
              form.getFieldValue("type") === undefined
            }
          >
            Enregistrer les interventions
          </Button>
        </Space>
      </Form>
    </Drawer>
  );
}
