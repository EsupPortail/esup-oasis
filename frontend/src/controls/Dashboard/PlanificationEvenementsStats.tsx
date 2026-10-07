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
import { IStatistiquesEvenements, ITypeEvenement } from "@api";
import {
  TypeAffichageValues,
  useAffichageFiltres,
} from "@context/affichageFiltres/AffichageFiltresContext";
import { AffectationFilterValues } from "@controls/Filters/Affectation/AffectationFilter";
import StatisticProgress from "@controls/Dashboard/StatisticProgress";
import Statistic from "@controls/Dashboard/Statistic";

interface IPlanificationEvenementsStatsProps {
  stats: IStatistiquesEvenements | undefined;
  typesEvenements: ITypeEvenement[] | undefined;
  isFetching: boolean;
}

/**
 * Bloc "Planification des évènements" du tableau de bord.
 */
export default function PlanificationEvenementsStats({
  stats,
  typesEvenements,
  isFetching,
}: IPlanificationEvenementsStatsProps): ReactElement {
  const user = useAuth().user;
  const navigate = useNavigate();
  const { setAffichageFiltres } = useAffichageFiltres();

  function goToCalendar(type: TypeAffichageValues, affecte: AffectationFilterValues) {
    setAffichageFiltres(
      { type },
      {
        debut: new Date(),
        fin: new Date(),
        "exists[intervenant]": affecte,
        type:
          affecte === AffectationFilterValues.NonAffectes
            ? typesEvenements?.map((t) => t["@id"] as string)
            : undefined,
      },
    );
    navigate(`/planning/calendrier`);
  }

  return (
    <Col span={24} className="mt-2">
      <Typography.Title level={2}>Planification des évènements</Typography.Title>
      <Row gutter={[16, 16]}>
        <Col xl={6} lg={12} xs={24} sm={12}>
          <Card
            className="pointer ant-card-stats-hoverable"
            variant="borderless"
            onClick={() => goToCalendar("day", AffectationFilterValues.Tous)}
          >
            <StatisticProgress
              done={(stats?.evenementsJour || 0) - (stats?.evenementsNonAffectesJour || 0)}
              title="Aujourd'hui"
              total={stats?.evenementsJour || 0}
              isFetching={isFetching}
              evolution={stats?.evolutionJour || 0}
            />
          </Card>
        </Col>
        <Col xl={6} lg={12} xs={24} sm={12}>
          <Card
            className="pointer ant-card-stats-hoverable"
            variant="borderless"
            onClick={() => goToCalendar("work_week", AffectationFilterValues.Tous)}
          >
            <StatisticProgress
              done={(stats?.evenementsSemaine || 0) - (stats?.evenementsNonAffectesSemaine || 0)}
              title="Cette semaine"
              total={stats?.evenementsSemaine || 0}
              isFetching={isFetching}
              evolution={stats?.evolutionSemaine || 0}
            />
          </Card>
        </Col>
        <Col xl={6} lg={12} xs={24} sm={12}>
          <Card
            className="pointer ant-card-stats-hoverable"
            variant="borderless"
            onClick={() => goToCalendar("month", AffectationFilterValues.Tous)}
          >
            <StatisticProgress
              done={(stats?.evenementsMois || 0) - (stats?.evenementsNonAffectesMois || 0)}
              title="Ce mois-ci"
              total={stats?.evenementsMois || 0}
              isFetching={isFetching}
              evolution={stats?.evolutionMois || 0}
            />
          </Card>
        </Col>
        {user?.isGestionnaire && (
          <Col xl={6} lg={12} xs={24} sm={12}>
            <Card
              className="pointer ant-card-stats-numeric ant-card-stats-hoverable"
              variant="borderless"
              onClick={() => navigate(`/interventions/renforts`)}
            >
              <Statistic
                title={pluriel(
                  stats?.evenementsEnAttenteDeValidation || 0,
                  "Intervention à valider",
                  "Interventions à valider",
                )}
                value={stats?.evenementsEnAttenteDeValidation || 0}
                precision={0}
                isFetching={isFetching}
                prefix={
                  stats?.evenementsEnAttenteDeValidation &&
                  stats?.evenementsEnAttenteDeValidation > 0 ? (
                    <Tooltip
                      title={`${stats?.evenementsEnAttenteDeValidation} ${pluriel(
                        stats?.evenementsEnAttenteDeValidation,
                        "intervention",
                        "interventions",
                      )} de renforts à valider`}
                    >
                      <WarningFilled className="text-warning mr-1" />
                    </Tooltip>
                  ) : undefined
                }
              />
            </Card>
          </Col>
        )}
      </Row>
    </Col>
  );
}
