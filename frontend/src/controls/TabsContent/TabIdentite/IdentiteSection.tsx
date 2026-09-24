/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Col, Descriptions, Row, Skeleton, Space, Typography } from "antd";
import { MinusOutlined, UserOutlined } from "@ant-design/icons";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import dayjs from "dayjs";
import { IUtilisateur } from "@api";
import { useAuth } from "@/auth/AuthProvider";
import { GenreItem } from "@controls/Items/GenreItem";
import { UtilisateurEmailItem } from "@controls/Items/UtilisateurEmailItem";
import { calculerAge } from "@utils/dates";
import { MailSmallButton } from "@controls/Forms/MailSmallButton";
import UtilisateurAvatarImage from "@controls/Avatars/UtilisateurAvatarImage";

interface IdentiteSectionProps {
  utilisateur: IUtilisateur;
  isFetching: boolean;
  mutateUtilisateur: (params: { data: Partial<IUtilisateur>; "@id": string }) => void;
}

export const IdentiteSection: React.FC<IdentiteSectionProps> = ({
  utilisateur,
  isFetching,
  mutateUtilisateur,
}) => {
  const screens = useBreakpoint();
  const user = useAuth().user;

  return (
    <Row
      gutter={16}
      className={screens.xl ? "" : "d-flex-column-reverse"}
      style={{ alignItems: "center" }}
    >
      <Col xs={24} xl={18}>
        {isFetching ? (
          <Skeleton active paragraph />
        ) : (
          <>
            <h2>Identité</h2>
            <Descriptions bordered column={screens.xl ? 2 : 1}>
              <Descriptions.Item label="Nom" span={1}>
                <span className="semi-bold text-primary">
                  {utilisateur.nom?.toLocaleUpperCase()}
                </span>
              </Descriptions.Item>
              <Descriptions.Item label="Prénom" span={1}>
                <span className="semi-bold text-primary">{utilisateur.prenom}</span>
              </Descriptions.Item>
              <Descriptions.Item label="Genre">
                <GenreItem genre={utilisateur.genre} />
              </Descriptions.Item>
              <Descriptions.Item label="Date de naissance">
                {utilisateur.dateNaissance ? (
                  <Space>
                    <span>{dayjs(utilisateur.dateNaissance).format("DD/MM/YYYY")}</span>
                    <span className="semi-bold">
                      ({calculerAge(utilisateur.dateNaissance)} ans)
                    </span>
                  </Space>
                ) : (
                  <MinusOutlined />
                )}
              </Descriptions.Item>
              <Descriptions.Item span={user?.isGestionnaire ? 1 : 2} label="Email institutionnel">
                {utilisateur?.email ? (
                  <Space>
                    <UtilisateurEmailItem utilisateur={utilisateur} emailPerso={false} />
                    <MailSmallButton utilisateur={utilisateur} className="text-primary" />
                  </Space>
                ) : (
                  <MinusOutlined />
                )}
              </Descriptions.Item>
              {user?.isGestionnaire && (
                <Descriptions.Item label="Contact en cas d'urgence">
                  <Typography.Text
                    style={{ whiteSpace: "pre-wrap" }}
                    editable={{
                      text: utilisateur?.contactUrgence ?? undefined,
                      onChange: (value) =>
                        mutateUtilisateur({
                          data: {
                            contactUrgence: value,
                          },
                          "@id": utilisateur["@id"] as string,
                        }),
                    }}
                  >
                    {utilisateur?.contactUrgence || <MinusOutlined />}
                  </Typography.Text>
                </Descriptions.Item>
              )}
              {user?.isGestionnaire && (
                <Descriptions.Item label="Email personnel">
                  <Space>
                    <UtilisateurEmailItem
                      utilisateur={utilisateur}
                      emailPerso
                      onEdit={(value) =>
                        mutateUtilisateur({
                          data: {
                            emailPerso: value,
                          },
                          "@id": utilisateur["@id"] as string,
                        })
                      }
                    />
                    <MailSmallButton
                      utilisateur={utilisateur}
                      emailPerso
                      className="text-primary"
                    />
                  </Space>
                </Descriptions.Item>
              )}
              {user?.isGestionnaire && (
                <Descriptions.Item label="Téléphone personnel">
                  <Typography.Text
                    editable={{
                      text: utilisateur?.telPerso ?? undefined,
                      onChange: (value) =>
                        mutateUtilisateur({
                          data: {
                            telPerso: value,
                          },
                          "@id": utilisateur["@id"] as string,
                        }),
                    }}
                  >
                    {utilisateur?.telPerso || <MinusOutlined />}
                  </Typography.Text>
                </Descriptions.Item>
              )}
              {utilisateur.adresse && (
                <Descriptions.Item label="Adresse postale" span={2}>
                  <span style={{ whiteSpace: "pre-line" }}>
                    {[
                      utilisateur.adresse.ligne1,
                      utilisateur.adresse.ligne2,
                      [utilisateur.adresse.codePostal, utilisateur.adresse.ville]
                        .filter(Boolean)
                        .join(" "),
                      utilisateur.adresse.pays,
                    ]
                      .filter(Boolean)
                      .join("\n")}
                  </span>
                </Descriptions.Item>
              )}
            </Descriptions>
          </>
        )}
      </Col>
      <Col xs={24} xl={6} className="d-flex-center">
        <UtilisateurAvatarImage
          utilisateurId={utilisateur["@id"] as string}
          as="img"
          height={250}
          style={{ fontSize: 128 }}
          className={`border-0 mt-3 ${screens.xl ? "" : "mb-3"}`}
          fallback={<UserOutlined />}
          desactiverLazyLoading
        />
      </Col>
    </Row>
  );
};
