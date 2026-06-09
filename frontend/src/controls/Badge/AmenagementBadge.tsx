/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Badge } from "antd";
import React, { useMemo } from "react";
import { useApi } from "../../context/api/ApiProvider";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { DomaineAmenagementInfos, getDomaineAmenagement } from "../../lib/amenagements";
import { PREFETCH_TYPES_AMENAGEMENTS } from "../../api/ApiPrefetchHelpers";

/**
 * Badge pour afficher le nombre d'aménagements d'un domaine ou de la décision pour un utilisateur
 * @param props
 * @constructor
 */
export default function AmenagementBadge(props: {
   utilisateurId: string;
   domaineAmenagement?: DomaineAmenagementInfos;
   decision?: boolean;
}) {
   const { data: types } = useApi().useGetCollection(PREFETCH_TYPES_AMENAGEMENTS);
   const { data: amenagements } = useApi().useGetCollectionPaginated({
      path: "/utilisateurs/{uid}/amenagements",
      parameters: {
         uid: props.utilisateurId,
      },
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
   });
   const nb = useMemo(() => {
      if (props.decision) {
         return amenagements?.items
            .map((a) => {
               const type = types?.items.find((t) => t["@id"] === a.typeAmenagement);
               return type;
            })
            .filter((t) => t?.decision)
            .length;
      } else {
         return amenagements?.items
            .map((a) => {
               const type = types?.items.find((t) => t["@id"] === a.typeAmenagement);
               return getDomaineAmenagement(type);
            })
            .filter((d) => !props.domaineAmenagement?.id || props.domaineAmenagement.id === d?.id)
            .length;
      }
   }, [amenagements, types, props.domaineAmenagement, props.decision]);

   return nb && nb > 0 ? <Badge color="cyan" size="small" count={nb} /> : null;
}
