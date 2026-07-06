/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useState } from "react";
import { Avatar, Button, Card, Col, Flex, Row, Tooltip, Typography } from "antd";
import { useNavigate } from "react-router-dom";
import { DownOutlined } from "@ant-design/icons";
import { pluriel } from "@utils/string";
import { IEtatDemande, IStatistiquesEvenements } from "@api";
import Statistic from "@controls/Dashboard/Statistic";
import MonoStackedBar from "@controls/Dashboard/MonoStackedBar/MonoStackedBar";
import { getEtatDemandeInfo } from "@lib";
import { getContrastColor } from "@utils/colors";

interface IDemandesAccompagnementStatsProps {
  stats: IStatistiquesEvenements | undefined;
  etatsDemande: IEtatDemande[] | undefined;
  isFetching: boolean;
}

/**
 * Bloc "Demandes d'accompagnement" du tableau de bord.
 */
export default function DemandesAccompagnementStats({
  stats,
  etatsDemande,
  isFetching,
}: IDemandesAccompagnementStatsProps): ReactElement {
  const navigate = useNavigate();
  const [showDemandeDetails, setShowDemandeDetails] = useState<boolean>(false);

  const etatsSorted = Object.keys(stats?.nbDemandesParEtat ?? {}).sort((e1, e2) => {
    const etat1 = getEtatDemandeInfo(e1);
    const etat2 = getEtatDemandeInfo(e2);
    return (etat1?.ordre || 0) - (etat2?.ordre || 0);
  });

  return (
    <Col span={24}>
      <Typography.Title level={2}>Demandes d'accompagnement</Typography.Title>
      <Row gutter={[16, 16]}>
        <Col xl={6} lg={12} xs={24} sm={12}>
          <Card
            className="pointer ant-card-stats-numeric ant-card-stats-hoverable"
            variant="borderless"
            onClick={() => navigate(`/demandeurs`)}
          >
            <Statistic
              title={pluriel(stats?.nbDemandesEnCours || 0, "Demande", "Demandes")}
              value={stats?.nbDemandesEnCours || 0}
              precision={0}
              isFetching={isFetching}
            />
          </Card>
        </Col>
      </Row>
      {(stats?.nbDemandesEnCours || 0) > 0 && (
        <div className="stats-subrow-wrapper appear-down">
          <div className="stats-subrow">
            <Flex align="center" wrap={false} className="w-100">
              <div className="ml-1" style={{ flexGrow: 1 }}>
                <MonoStackedBar
                  onClick={() => setShowDemandeDetails(!showDemandeDetails)}
                  data={etatsSorted.map((key) => {
                    const etat = etatsDemande?.find((e) => e["@id"] === key);
                    const etatInfos = getEtatDemandeInfo(key);
                    return {
                      value: parseInt(
                        (stats?.nbDemandesParEtat as unknown as Record<string, string>)?.[key] ||
                          "0",
                      ),
                      tooltip: etat?.libelle || "",
                      color: etatInfos?.hexColor || etatInfos?.color || "grey",
                      foreground: etatInfos?.hexColor
                        ? getContrastColor(etatInfos.hexColor as string)
                        : "#000000",
                    };
                  })}
                />
              </div>
              <Tooltip title="Détails des demandes">
                <Button
                  size="small"
                  type="link"
                  className="ml-1"
                  icon={showDemandeDetails ? <DownOutlined /> : <DownOutlined rotate={270} />}
                  onClick={() => setShowDemandeDetails(!showDemandeDetails)}
                />
              </Tooltip>
            </Flex>
            {showDemandeDetails && (
              <Row gutter={[16, 16]} className="mt-2">
                {etatsSorted.map((key) => {
                  const etat = etatsDemande?.find((e) => e["@id"] === key);
                  return (
                    <Col key={key} xl={6} lg={12} xs={24} sm={12}>
                      <Card
                        className="pointer ant-card-stats-numeric ant-card-stats-hoverable"
                        variant="borderless"
                        onClick={() => navigate(`/demandeurs?filtreType=etat&filtreValeur=${key}`)}
                      >
                        <Statistic
                          title={
                            <Flex align="center" gap={8}>
                              <Avatar
                                size={12}
                                style={{
                                  backgroundColor: getEtatDemandeInfo(key)?.hexColor,
                                }}
                              />
                              <span>{etat?.libelle}</span>
                            </Flex>
                          }
                          value={parseInt(
                            (stats?.nbDemandesParEtat as unknown as Record<string, string>)?.[
                              key
                            ] || "0",
                          )}
                          precision={0}
                          isFetching={isFetching}
                        />
                      </Card>
                    </Col>
                  );
                })}
              </Row>
            )}
          </div>
        </div>
      )}
    </Col>
  );
}
