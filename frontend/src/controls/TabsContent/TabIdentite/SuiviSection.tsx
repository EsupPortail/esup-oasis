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
  Descriptions,
  Empty,
  Flex,
  Form,
  FormInstance,
  Skeleton,
  theme,
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
  const { token } = theme.useToken();

  return (
    <>
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
                    <InfoCircleOutlined aria-hidden style={{ color: token.colorTextDescription }} />
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
                          if (!value) return;

                          // value doit être un nombre de 8 chiffres
                          if (!/^\d{8}$/.test(value)) {
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
            <h3 className="sr-only">Profils</h3>
            {(utilisateur?.profils || []).length === 0 && (
              <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Aucun profil actuellement" />
            )}
            <Form form={form} initialValues={utilisateur}>
              <TabProfils utilisateur={utilisateur} />
            </Form>
          </Descriptions.Item>
        </Descriptions>
      )}
    </>
  );
};
