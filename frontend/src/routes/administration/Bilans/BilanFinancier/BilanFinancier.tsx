/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { Breadcrumb, Button, Card, DatePicker, Form, Layout, Space, Typography } from "antd";
import { NavLink } from "react-router-dom";
import { FilterOutlined, HomeFilled } from "@ant-design/icons";
import React, { useState } from "react";
import { IProfil, Paths } from "@api";
import apiDownloader from "@utils/apiDownloader";
import { useAuth } from "@/auth/AuthProvider";
import ProfilsField from "@controls/Forms/ProfilsField";
import { env } from "@/env";

type FiltreBilan = { "profil[]": string[] };
type PathBilan = Paths["/suivis/financiers/debut/{debut}/fin/{fin}"]["get"]["parameters"]["path"];

/**
 * Compute the activity report for financial aid.
 *
 * @return {React.ReactElement} - The activity report component.
 */
export default function BilanFinancier(): React.ReactElement {
  const [form] = Form.useForm();
  const auth = useAuth();
  const [fetching, setFetching] = useState(false);
  const apiPrefix = env.REACT_APP_API_PREFIX.replace(/\/$/, "");

  function fetchBilan(
    options: { path: PathBilan; filtre?: FiltreBilan },
    accept: string,
    extension: string,
  ) {
    setFetching(true);
    apiDownloader(
      `${env.REACT_APP_API + apiPrefix}/suivis/financiers/debut/${options.path.debut}/fin/${
        options.path.fin
      }?${
        options.filtre && options.filtre["profil[]"]
          ? options.filtre["profil[]"].map((p) => `profil[]=${p}`).join("&")
          : ""
      }`,
      auth,
      {
        Accept: accept,
      },
      `${env.REACT_APP_TITRE?.toLocaleUpperCase()}_BilanFinancier_${options.path.debut}_${
        options.path.fin
      }.${extension}`,
    )
      .then()
      .finally(() => setFetching(false));
  }

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
            key: "bilan-financier",
            title: `Bilan financier`,
          },
        ]}
      />
      <Typography.Title level={1}>Bilans</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Bilan financier d'aide humaine
      </Typography.Title>
      <Form
        form={form}
        layout="vertical"
        onFinish={(values) => {
          if (values["dates[]"]) {
            fetchBilan(
              {
                path: {
                  debut: values["dates[]"][0].format("YYYY-MM-DD"),
                  fin: values["dates[]"][1].format("YYYY-MM-DD"),
                },
                filtre: {
                  "profil[]": values["profils[]"]?.map((p: IProfil) => p["@id"]),
                },
              },
              "text/csv",
              "csv",
            );
          }
        }}
      >
        <Card
          title={
            <Space>
              <FilterOutlined />
              Filtres du bilan
            </Space>
          }
          actions={[
            <Button size="large" type="primary" htmlType="submit" loading={fetching}>
              Demander le bilan
            </Button>,
            <Button
              size="large"
              onClick={() => {
                form.resetFields();
              }}
            >
              Réinitialiser
            </Button>,
          ].filter((a) => a)}
        >
          <Form.Item name="dates[]" label="Dates du bilan" required rules={[{ required: true }]}>
            <DatePicker.RangePicker
              className="text-center"
              picker="date"
              format="DD/MM/YYYY"
              allowEmpty={[false, false]}
            />
          </Form.Item>
          <Form.Item name="profils[]" label="Profils">
            <ProfilsField mode="tags" seulementActifs />
          </Form.Item>
        </Card>
      </Form>
    </Layout.Content>
  );
}
