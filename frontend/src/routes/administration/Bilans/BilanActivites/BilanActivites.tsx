/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import {
  Breadcrumb,
  Card,
  Col,
  DatePicker,
  Divider,
  Form,
  Layout,
  Modal,
  Row,
  Space,
  Typography,
} from "antd";
import { NavLink } from "react-router-dom";
import { HomeFilled } from "@ant-design/icons";
import React from "react";
import { IComposante, IFormation, IProfil, IUtilisateur, Paths, QK_SUIVIS_ACTIVITE } from "@api";
import ProfilsField from "@controls/Forms/ProfilsField";
import ComposantesField from "@controls/Forms/ComposantesField";
import FormationsField from "@controls/Forms/FormationsField";
import GestionnairesField from "@controls/Forms/GestionnairesField";
import { Dayjs } from "dayjs";
import { useApi } from "@context/api/ApiProvider";
import { TableBilanActivite } from "@controls/Admin/Bilans/TableBilanActivite";
import { env } from "@/env";

type FiltreBilan =
  Paths["/suivis/activite"]["post"]["requestBody"]["content"]["application/ld+json"];
export type FiltreBilanActivitesForm = FiltreBilan & {
  "dates[]": [Dayjs, Dayjs];
  "profilForm[]": IProfil[];
  "composanteForm[]": IComposante[];
  "formationForm[]": IFormation[];
  "gestionnaireForm[]": IUtilisateur[];
};

/**
 * Compute the activity report for financial aid.
 *
 * @return {React.ReactElement} - The activity report component.
 */
export default function BilanActivites(): React.ReactElement {
  const [ajouterBilan, setAjouterBilan] = React.useState<boolean>(false);

  const [form] = Form.useForm();

  const mutationPost = useApi().usePost({
    path: "/suivis/activite",
    invalidationQueryKeys: [QK_SUIVIS_ACTIVITE],
    onSuccess: () => {
      setAjouterBilan(false);
    },
  });

  return (
    <Layout.Content className="administration" style={{ padding: "0 50px" }}>
      <Breadcrumb
        className="mt-2"
        items={[
          {
            key: "bilans",
            title: (
              <NavLink to="/bilans">
                <Space>
                  <HomeFilled />
                  Bilans
                </Space>
              </NavLink>
            ),
          },
          {
            key: "bilan-activite",
            title: `Bilan activités ${env.REACT_APP_SERVICE}`,
          },
        ]}
      />
      <Typography.Title level={1}>Bilans</Typography.Title>

      <Typography.Title level={2} className="mt-0 mb-4">
        Bilan activités {env.REACT_APP_SERVICE}
      </Typography.Title>

      <TableBilanActivite setAjouterBilan={setAjouterBilan} form={form} />

      <Modal
        open={ajouterBilan}
        title={<>Demander un bilan d'activités</>}
        onCancel={() => setAjouterBilan(false)}
        onOk={() => form.submit()}
        okText="Demander"
      >
        <Form<FiltreBilanActivitesForm>
          form={form}
          layout="vertical"
          onFinish={(values) => {
            if (values["dates[]"]) {
              mutationPost.mutate({
                data: {
                  debut: values["dates[]"][0].format("YYYY-MM-DD"),
                  fin: values["dates[]"][1].format("YYYY-MM-DD"),
                  profils: values["profilForm[]"]?.map((p) => p["@id"] as string),
                  composantes: values["composanteForm[]"]?.map((p) => p["@id"] as string),
                  formations: values["formationForm[]"]?.map((p) => p["@id"] as string),
                  gestionnaires: values["gestionnaireForm[]"]?.map((p) => p["@id"] as string),
                },
              });
            }
          }}
        >
          <Card>
            <Row gutter={[32, 16]}>
              <Col xs={24} sm={24}>
                <Row>
                  <Col xs={24} sm={24} md={8}>
                    Dates du bilan<span className="text-danger">*</span>
                  </Col>
                  <Col xs={24} sm={24} md={16}>
                    <Form.Item
                      name="dates[]"
                      required
                      rules={[
                        {
                          required: true,
                          message: "Vous devez préciser les dates du bilan",
                        },
                      ]}
                    >
                      <DatePicker.RangePicker
                        className="text-center w-100"
                        picker="date"
                        format="DD/MM/YYYY"
                        allowEmpty={[false, false]}
                      />
                    </Form.Item>
                  </Col>
                  <Col xs={0} sm={0} lg={24}>
                    <Divider />
                  </Col>
                  <Col xs={24} sm={24} md={8}>
                    Profils
                  </Col>
                  <Col xs={24} sm={24} md={16}>
                    <Form.Item name="profilForm[]">
                      <ProfilsField mode="tags" seulementActifs />
                    </Form.Item>
                  </Col>

                  <Col xs={24} sm={24} md={8}>
                    Chargé•e d'accomp.
                  </Col>
                  <Col xs={24} sm={24} md={16}>
                    <Form.Item name="gestionnaireForm[]">
                      <GestionnairesField mode="tags" />
                    </Form.Item>
                  </Col>
                </Row>
              </Col>

              <Col xs={24} sm={24}>
                <Row>
                  <Col xs={0} sm={0} lg={24}>
                    <Divider className="mt-0" />
                  </Col>
                  <Col xs={24} sm={24} md={8}>
                    Composantes
                  </Col>
                  <Col xs={24} sm={24} md={16}>
                    <Form.Item name="composanteForm[]" className="mt-05">
                      <ComposantesField mode="tags" />
                    </Form.Item>
                  </Col>
                  <Col xs={24} sm={24} md={8}>
                    Formations
                  </Col>
                  <Col xs={24} sm={24} md={16}>
                    <Form.Item name="formationForm[]">
                      <FormationsField mode="tags" />
                    </Form.Item>
                  </Col>
                </Row>
              </Col>
            </Row>
          </Card>
        </Form>
      </Modal>
    </Layout.Content>
  );
}
