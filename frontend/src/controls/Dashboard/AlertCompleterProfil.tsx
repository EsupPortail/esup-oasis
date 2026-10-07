/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { useAuth } from "@/auth/AuthProvider";
import { useNavigate } from "react-router";
import { Alert, Button, Space, Typography } from "antd";

export default function AlertCompleterProfil(): ReactElement {
  const user = useAuth().user;
  const navigate = useNavigate();
  const profilComplete = (user?.campus?.length || 0) > 0 && (user?.competences?.length || 0) > 0;

  if (user?.isAdmin || !user?.isIntervenant) return <></>;

  if (!profilComplete)
    return (
      <Alert
        type="info"
        showIcon
        title="Profil incomplet"
        description={
          <div>
            <Space orientation="vertical">
              <Typography.Text>
                <b>Votre profil est incomplet</b>. Veuillez compléter votre/vos campus et vos
                compétences afin que les évènements qui vous seront attribués vous correspondent.
                <br />
                Vous pouvez également personnaliser vos préférences de notifications depuis votre
                profil.
              </Typography.Text>
              <Button onClick={() => navigate("/profil")}>Compléter mon profil</Button>
            </Space>
          </div>
        }
      />
    );

  return <></>;
}
