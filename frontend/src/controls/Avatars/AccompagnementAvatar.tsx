/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Avatar, Space, Tooltip } from "antd";
import { CheckCircleOutlined, CloseCircleOutlined } from "@ant-design/icons";
import { env } from "../../env";

export function AccompagnementAvatar(props: {
   avecAccompagnement?: boolean;
   size?: "small" | "default" | "large";
   libelle?: boolean;
}) {
   return (
      <Tooltip
         title={
            props.avecAccompagnement
               ? "Bénéficiaire ayant demandé un accompagnement " + env.REACT_APP_SERVICE
               : "Bénéficiaire n'ayant pas demandé d'accompagnement " + env.REACT_APP_SERVICE
         }
      >
         <Space size={"small"}>
            <Avatar
               icon={
                  props.avecAccompagnement ? (
                     <CheckCircleOutlined aria-hidden />
                  ) : (
                     <CloseCircleOutlined aria-hidden />
                  )
               }
               size={props.size || "default"}
               className={
                  props.avecAccompagnement
                     ? "text-text bg-transparent"
                     : "text-warning bg-transparent"
               }
            />
            {props.libelle && (
               <span className="text-xs">
                  {props.avecAccompagnement ? "Avec accompagnement" : "Sans accompagnement"}
               </span>
            )}
         </Space>
      </Tooltip>
   );
}
