/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useState } from "react";
import {
  Button,
  Card,
  Col,
  Form,
  Layout,
  notification,
  Row,
  Skeleton,
  Tabs,
  Typography,
} from "antd";
import { HomeOutlined, SaveOutlined } from "@ant-design/icons";
import { useApi } from "@context/api/ApiProvider";
import { useAuth } from "@/auth/AuthProvider";
import { useNavigate } from "react-router-dom";
import { IUtilisateur, PREFETCH_CAMPUS, QK_UTILISATEURS } from "@api";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { MonProfilContact } from "@controls/Profil/MonProfilContact";
import { MonProfilCampus } from "@controls/Profil/MonProfilCampus";
import { MonProfilCompetences } from "@controls/Profil/MonProfilCompetences";
import { MonProfilSynchro } from "@controls/Profil/MonProfilSynchro";
import {
  MonProfilNotification,
  notificationFrequences,
} from "@controls/Profil/MonProfilNotification";
import { MonProfilContactPhase } from "@controls/Profil/MonProfilContactPhase";
import "@routes/commun/MonProfil.scss";
import { env } from "@/env";
import { RoleApi, service } from "@lib";

/**
 * Renders the current user's profile page.
 * Allows the user to edit his profile.
 *
 * @returns {ReactElement} The rendered profile page.
 */
export default function MonProfil(): ReactElement {
  const user = useAuth().user;
  const navigate = useNavigate();
  const [currentTab, setCurrentTab] = useState("contact");
  const { data: utilisateur, isFetching } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: user?.["@id"] as string,
    enabled: !!user,
  });
  const { data: dataCampus } = useApi().useGetFullCollection(PREFETCH_CAMPUS);
  const { data: dataCompetences } = useApi().useGetFullCollection({
    path: "/competences",
  });
  const screens = useBreakpoint();
  const mutationPatch = useApi().usePatch({
    path: `/utilisateurs/{uid}`,
    invalidationQueryKeys: [QK_UTILISATEURS],
    onSuccess: () => {
      notification.success({
        title: "Votre profil a été mis à jour",
        description: (
          <Button icon={<HomeOutlined />} onClick={() => navigate("/dashboard")}>
            Revenir à l'accueil
          </Button>
        ),
      });
      navigate("/profil");
    },
  });
  const tabsHiddenButtons = ["synchronisation", "contactPhase"];

  if (isFetching) {
    return (
      <Layout.Content style={{ padding: "0 50px" }}>
        <Typography.Title level={1}>Mon profil</Typography.Title>
        <Skeleton active />
      </Layout.Content>
    );
  }

  return (
    <Layout.Content style={{ padding: "0 50px" }}>
      <Typography.Title level={1}>Mon profil</Typography.Title>
      <Card>
        <Form
          layout="vertical"
          onFinish={(values) => {
            const valuesToSend = {
              ...values,
              nom: undefined,
              prenom: undefined,
              email: undefined,
            };

            notificationFrequences.forEach((freq) => {
              valuesToSend[freq.key] = (values.notifications || [])?.includes(freq.key);
            });

            mutationPatch.mutate({
              data: valuesToSend,
              "@id": user?.["@id"] as string,
            });
          }}
          initialValues={{
            ...utilisateur,
            notifications: notificationFrequences
              .filter((freq) => freq.roles.some((role) => user?.roles?.includes(role as RoleApi)))
              .filter((freq) => freq.visible)
              .map((freq) => {
                if (utilisateur?.[freq.key as keyof IUtilisateur]) {
                  return freq.key;
                }
                return null;
              })
              .filter((freq) => freq !== null),
          }}
        >
          <Tabs
            aria-label="Sections du profil"
            defaultActiveKey="contact"
            tabPlacement={screens.lg ? "start" : undefined}
            onChange={(tab) => setCurrentTab(tab)}
            className="monprofil-tabs"
            items={
              [
                {
                  key: "contact",
                  label: "Pour vous contacter",
                  children: (
                    <Row gutter={[16, 16]}>
                      <MonProfilContact />
                    </Row>
                  ),
                },
                user?.isBeneficiaire &&
                  (user?.gestionnairesActifs || []).length > 0 && {
                    key: "contactPhase",
                    label: `Votre contact ${service.sigle}`,
                    children: (
                      <Row gutter={[16, 16]}>
                        <MonProfilContactPhase user={user} />
                      </Row>
                    ),
                  },
                user?.isIntervenant && {
                  key: "campus",
                  label: "Campus",
                  children: (
                    <Row gutter={[16, 16]}>
                      <MonProfilCampus campuses={dataCampus?.items || []} user={user} />
                    </Row>
                  ),
                },
                user?.isIntervenant && {
                  key: "competences",
                  label: "Compétences",
                  children: (
                    <Row gutter={[16, 16]}>
                      <MonProfilCompetences
                        competences={dataCompetences?.items || []}
                        user={user}
                      />
                    </Row>
                  ),
                },
                {
                  key: "notifications",
                  label: "Préférences de notification",
                  children: (
                    <Row gutter={[16, 16]}>
                      <MonProfilNotification user={user} />
                    </Row>
                  ),
                },
                env.REACT_APP_NOM_SERVICE_SYNCHRO && {
                  key: "synchronisation",
                  label: "Synchronisation des évènements",
                  children: (
                    <Row gutter={[16, 16]}>
                      <MonProfilSynchro />
                    </Row>
                  ),
                },
              ]
                // On supprime les tabs masqués (false, null, undefined)
                .filter(Boolean) as { key: string; label: string }[]
            }
          />

          {!tabsHiddenButtons.includes(currentTab) && (
            <Col span={24} className="mt-3 text-center">
              <Form.Item>
                <Button size="large" icon={<SaveOutlined />} type="primary" htmlType="submit">
                  Enregistrer
                </Button>
              </Form.Item>
            </Col>
          )}
        </Form>
      </Card>
    </Layout.Content>
  );
}
