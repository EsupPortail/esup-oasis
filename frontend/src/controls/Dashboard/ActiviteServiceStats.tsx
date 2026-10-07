/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Card, Col, Row, Tooltip, Typography } from "antd";
import { useNavigate } from "react-router";
import { WarningFilled } from "@ant-design/icons";
import { pluriel } from "@utils/string";
import { useAuth } from "@/auth/AuthProvider";
import { IStatistiquesEvenements } from "@api";
import Statistic from "@controls/Dashboard/Statistic";
import { EtatDecisionEtablissement } from "@controls/Avatars/DecisionEtablissementAvatar";
import { EtatAvisEse } from "@controls/Avatars/BeneficiaireAvisEseAvatar";
import { BENEFICIAIRE_PROFIL_A_DETERMINER } from "@/constants";
import { env } from "@/env";

interface IActiviteServiceStatsProps {
  stats: IStatistiquesEvenements | undefined;
  isFetching: boolean;
}

/**
 * Bloc "Activité du service" du tableau de bord (réservé aux gestionnaires).
 */
export default function ActiviteServiceStats({
  stats,
  isFetching,
}: IActiviteServiceStatsProps): ReactElement | null {
  const user = useAuth().user;
  const navigate = useNavigate();

  if (!user?.isGestionnaire) return null;

  return (
    <Col span={24}>
      <Typography.Title level={2}>Activité du service</Typography.Title>
      <Row gutter={[16, 16]}>
        <Col xl={8} lg={12} xs={24} sm={12}>
          <Card
            className="pointer ant-card-stats-numeric ant-card-stats-hoverable"
            variant="borderless"
            onClick={() => navigate(`/beneficiaires`)}
          >
            <Statistic
              title={pluriel(stats?.nbBeneficiaires || 0, "Bénéficiaire", "Bénéficiaires")}
              value={stats?.nbBeneficiaires || 0}
              precision={0}
              isFetching={isFetching}
              prefix={
                stats?.nbBeneficiairesIncomplets && stats?.nbBeneficiairesIncomplets > 0 ? (
                  <Tooltip
                    title={`${stats?.nbBeneficiairesIncomplets} ${pluriel(
                      stats?.nbBeneficiairesIncomplets,
                      "profil",
                      "profils",
                    )} à renseigner`}
                  >
                    <WarningFilled className="text-warning mr-1" />
                  </Tooltip>
                ) : undefined
              }
            />
          </Card>
        </Col>
        <Col xl={8} lg={12} xs={24} sm={12}>
          <Card
            className="pointer ant-card-stats-numeric ant-card-stats-hoverable"
            variant="borderless"
            onClick={() => navigate(`/amenagements?mode=beneficiaire`)}
          >
            <Statistic
              title={pluriel(stats?.nbAmenagementsEnCours || 0, "Aménagement", "Aménagements")}
              value={stats?.nbAmenagementsEnCours || 0}
              precision={0}
              isFetching={isFetching}
            />
          </Card>
        </Col>
        <Col xl={8} lg={12} xs={24} sm={12}>
          <Card
            className="pointer ant-card-stats-numeric ant-card-stats-hoverable"
            variant="borderless"
            onClick={() => navigate(`/intervenants`)}
          >
            <Statistic
              title={pluriel(stats?.nbIntervenants || 0, "Intervenant", "Intervenants")}
              value={stats?.nbIntervenants || 0}
              precision={0}
              isFetching={isFetching}
            />
          </Card>
        </Col>
      </Row>
      <div className="stats-subrow-wrapper appear-down">
        <div className="stats-subrow">
          <Row gutter={[16, 16]}>
            <Col xl={8} lg={12} xs={24} sm={12}>
              <Card
                className="pointer ant-card-stats-numeric ant-card-stats-hoverable"
                variant="borderless"
                onClick={() =>
                  navigate(
                    `/beneficiaires?filtreType=profil&filtreValeur=${BENEFICIAIRE_PROFIL_A_DETERMINER}`,
                  )
                }
              >
                <Statistic
                  title={pluriel(
                    stats?.nbBeneficiairesIncomplets || 0,
                    "Profil à renseigner",
                    "Profils à renseigner",
                  )}
                  value={stats?.nbBeneficiairesIncomplets || 0}
                  precision={0}
                  isFetching={isFetching}
                />
              </Card>
            </Col>
            <Col xl={8} lg={12} xs={24} sm={12}>
              <Card
                className="pointer ant-card-stats-numeric ant-card-stats-hoverable"
                variant="borderless"
                onClick={() =>
                  navigate(
                    `/beneficiaires?filtreType=etatAvisEse&filtreValeur=${EtatAvisEse.ETAT_EN_ATTENTE}`,
                  )
                }
              >
                <Statistic
                  title={`Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"} en attente`}
                  value={stats?.nbAvisEseEnAttente || 0}
                  precision={0}
                  isFetching={isFetching}
                />
              </Card>
            </Col>
            <Col xl={8} lg={12} xs={24} sm={12}>
              <Card
                className="pointer ant-card-stats-numeric ant-card-stats-hoverable"
                variant="borderless"
                onClick={() =>
                  navigate(
                    `/beneficiaires?filtreType=etatDecisionAmenagement&filtreValeur=${
                      user?.isAdmin
                        ? EtatDecisionEtablissement.VALIDE
                        : EtatDecisionEtablissement.ATTENTE_VALIDATION_CAS
                    }`,
                  )
                }
              >
                <Statistic
                  title={
                    user?.isAdmin
                      ? pluriel(
                          stats?.nbDecisionsAEditer || 0,
                          "Décision à éditer",
                          "Décisions à éditer",
                        )
                      : pluriel(
                          stats?.nbDecisionsAttenteValidation || 0,
                          "Décision à valider",
                          "Décisions à valider",
                        )
                  }
                  value={
                    (user?.isAdmin
                      ? stats?.nbDecisionsAEditer
                      : stats?.nbDecisionsAttenteValidation) || 0
                  }
                  precision={0}
                  isFetching={isFetching}
                />
              </Card>
            </Col>
          </Row>
        </div>
      </div>
    </Col>
  );
}
