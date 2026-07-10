/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { ICampagneDemande } from "@api";
import { useApi } from "@context/api/ApiProvider";
import dayjs from "dayjs";

export function CampagneDemandeDateItem(props: {
  campagneDemande?: ICampagneDemande;
  campagneDemandeId?: string;
  templateString: string;
}) {
  const { data: campagneDemandeData } = useApi().useGetItem({
    path: "/types_demandes/{typeId}/campagnes/{id}",
    url: props.campagneDemandeId,
    enabled: !!props.campagneDemandeId,
  });
  const item = campagneDemandeData ?? props.campagneDemande;

  if (!item) return null;

  return props.templateString.replace(
    /{{(.*?)}}/g,
    (match, p1: "debut" | "fin" | "dateCommission") =>
      item?.[p1] ? dayjs(item[p1]).format("DD/MM/YYYY") : match,
  );
}
