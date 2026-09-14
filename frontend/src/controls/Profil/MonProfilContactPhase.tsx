/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { RoleValues, service, Utilisateur } from "@lib";
import React, { ReactElement } from "react";
import { Card, Col, Typography } from "antd";
import { GestionnaireItem } from "@controls/Items/GestionnaireItem";
import { UtilisateurAvatar } from "@controls/Avatars/UtilisateurAvatar";

export function MonProfilContactPhase(props: { user: Utilisateur | undefined }): ReactElement {
  return (
    <Col xs={24} sm={24} md={24} lg={24} xl={12}>
      <Typography.Title level={2}>
        {props.user?.gestionnairesActifs?.length === 1 ? "Votre contact" : "Vos contacts"}{" "}
        {service.sigle}
      </Typography.Title>
      <div>
        <span className="semi-bold mb-1">Votre accompagnement actuel est réalisé par</span>
        {props.user?.gestionnairesActifs?.map((gestionnaire) => (
          <div key={gestionnaire}>
            <Card className="mt-2">
              <Card.Meta
                title={
                  <div className="flex items-center">
                    <span className="mr-2">
                      <UtilisateurAvatar
                        role={RoleValues.ROLE_GESTIONNAIRE}
                        utilisateurId={gestionnaire}
                      />
                    </span>
                    <span className="text-lg">
                      <GestionnaireItem
                        gestionnaireId={gestionnaire}
                        showEmail
                        showAvatar={false}
                      />
                    </span>
                  </div>
                }
              />
            </Card>
          </div>
        ))}
      </div>
    </Col>
  );
}
