/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import "../../administration/Administration.scss";
import { Layout, Typography } from "antd";
import ValidationInterventionTable from "../../../controls/Table/ValidationInterventionTable";
import { env } from "../../../env";
import { TimezoneAlert } from "../../../controls/Calendar/TimezoneAlert";

/**
 * Renders a page to validate interventions of renforts.
 *
 * @returns {ReactElement} The rendered content.
 */
export default function ValidationInterventionsRenforts(): ReactElement {
   return (
      <Layout.Content style={{ padding: "0 50px" }}>
         <Typography.Title level={1}>Renforts service {env.REACT_APP_SERVICE}</Typography.Title>
         <Typography.Title level={2}>Interventions à valider</Typography.Title>
         <TimezoneAlert />
         <ValidationInterventionTable />
      </Layout.Content>
   );
}
