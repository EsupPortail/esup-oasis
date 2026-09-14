/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { memo, ReactElement } from "react";
import { Card, Col, Row, Typography } from "antd";
import RenfortInterventionAddImage from "@controls/Images/RenfortInterventionAddImage";
import { service } from "@lib";

export const MesInterventionsIntro = memo(
  (): ReactElement => (
    <>
      <Typography.Title level={2}>Mes interventions</Typography.Title>
      <Card className="mb-2 mt-2" variant="borderless">
        <Row>
          <Col span={16}>
            <Typography.Title level={3} className="mt-1">
              Consultez et complétez vos interventions
            </Typography.Title>
            <Typography.Paragraph>
              Vous pouvez voir ici les interventions que vous avez effectuées.
              <br />
              <br />
              En tant que renfort {service.sigle}, vous pouvez également saisir vos interventions.
              Celles-ci devront être validées par un chargé d'accompagnement pour pouvoir être
              envoyées à la RH pour paiement.
            </Typography.Paragraph>
          </Col>
          <Col span={8} className="d-flex-center">
            <RenfortInterventionAddImage style={{ width: "100%", maxHeight: 150 }} />
          </Col>
        </Row>
      </Card>
    </>
  ),
);
