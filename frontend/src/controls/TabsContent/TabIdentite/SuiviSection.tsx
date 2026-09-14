/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import {
  App,
  Col,
  Descriptions,
  Flex,
  Form,
  FormInstance,
  Skeleton,
  Tooltip,
  Typography,
} from "antd";
import { InfoCircleOutlined, LockOutlined, MinusOutlined } from "@ant-design/icons";
import { IUtilisateur } from "@api";
import { RoleValues, service } from "@lib";
import { TabProfils } from "@controls/TabsContent/TabProfils";

interface SuiviSectionProps {
  utilisateur: IUtilisateur;
  isFetching: boolean;
  mutateUtilisateur: (params: { data: Partial<IUtilisateur>; "@id": string }) => void;
  form: FormInstance;
}

export const SuiviSection: React.FC<SuiviSectionProps> = ({
  utilisateur,
  isFetching,
  mutateUtilisateur,
  form,
}) => {
  const { message } = App.useApp();

  return (
    <Col xs={24} xl={12}>
      <h2>Suivi {service.sigle}</h2>
      {isFetching ? (
        <Skeleton active paragraph />
      ) : (
        <Descriptions bordered column={1} style={{ overflowX: "auto" }}>
          {utilisateur.roles?.includes(RoleValues.ROLE_BENEFICIAIRE) && (
            <Descriptions.Item
              label={
                <Flex className="w-100" justify="space-between">
                  <div>
                    <LockOutlined className="mr-1" />
                    <span>Numéro d'anonymat</span>
                  </div>
                  <Tooltip title="Utilisé pour les rapports ministériels, il ne peut pas être modifié une fois saisi">
                    <InfoCircleOutlined className="text-legende" />
                  </Tooltip>
                </Flex>
              }
            >
              <Typography.Text
                editable={
                  utilisateur.numeroAnonyme
                    ? false
                    : {
                        onChange: (value) => {
                          // value doit être un nombre de 8 chiffres
                          if (value && !/^\d{8}$/.test(value)) {
                            message
                              .error("Le numéro d'anonymat doit être composé de 8 chiffres")
                              .then();
                            return;
                          }

                          mutateUtilisateur({
                            data: {
                              numeroAnonyme: parseInt(value),
                            },
                            "@id": utilisateur["@id"] as string,
                          });
                        },
                      }
                }
                copyable={
                  utilisateur.numeroAnonyme ? { text: utilisateur.numeroAnonyme.toString() } : false
                }
              >
                {utilisateur.numeroAnonyme ? (
                  <span className="code fs-09">{utilisateur.numeroAnonyme}</span>
                ) : (
                  <MinusOutlined />
                )}
              </Typography.Text>
            </Descriptions.Item>
          )}
          <Descriptions.Item label="Profils">
            {(utilisateur?.profils || []).length === 0 && (
              <div className="mb-1">Aucun profil actuellement</div>
            )}
            <Form form={form} initialValues={utilisateur} className="mt-1">
              <TabProfils utilisateur={utilisateur} />
            </Form>
          </Descriptions.Item>
        </Descriptions>
      )}
    </Col>
  );
};
