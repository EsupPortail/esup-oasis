/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useApi } from "../../context/api/ApiProvider";
import ProfilItem from "./ProfilItem";
import React from "react";
import { isEnCoursSurPeriode } from "../../utils/dates";
import { Skeleton, Space, Tooltip } from "antd";
import { useInView } from "react-intersection-observer";
import { InfoCircleOutlined } from "@ant-design/icons";
import { env } from "../../env";

export function BeneficiaireProfilItem(props: {
   profilBeneficiaire: string;
   masquerSiInactif?: boolean;
}) {
   const { ref, inView } = useInView();
   const { data } = useApi().useGetItem({
      path: "/utilisateurs/{uid}/profils/{id}",
      url: props.profilBeneficiaire as string,
      enabled: !!props.profilBeneficiaire && inView,
   });

   if (!data)
      return (
         <div ref={ref}>
            <Skeleton.Input className="mb-05" active />
         </div>
      );

   if (props.masquerSiInactif && !isEnCoursSurPeriode(data?.debut, data?.fin)) return null;

   return (
      <Space size={2} align={"center"}>
         <ProfilItem profil={data?.profil as string} maxWidth={170} />
         {data.avecAccompagnement === false && (
            <Tooltip title={"Bénéficiaire sans accompagnement " + env.REACT_APP_SERVICE}>
               <InfoCircleOutlined />
            </Tooltip>
         )}
      </Space>
   );
}
