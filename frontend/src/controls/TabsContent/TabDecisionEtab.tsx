/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Fabien Léon <fabien.leon@univ-brest.fr>
 */

import {
   getAmenagementsByCategories
} from "../../lib/amenagements";
import React, { useMemo } from "react";
import { IAmenagement } from "../../api/ApiTypeHelpers";
import { useApi } from "../../context/api/ApiProvider";
import {
   PREFETCH_CATEGORIES_AMENAGEMENTS,
   PREFETCH_TYPES_AMENAGEMENTS,
} from "../../api/ApiPrefetchHelpers";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { Empty, Flex, Row, Typography } from "antd";
import { useSearchParams } from "react-router-dom";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { BoutonDecisionEtab } from "./BoutonDecisionEtab";
import { DOMAINES_AMENAGEMENTS_INFOS } from "../../lib/amenagements";

export function TabDecisionEtab(props: {
   utilisateurId: string;
}) {
   const screens = useBreakpoint();
   const [searchParams] = useSearchParams();
   const [editedAmenagement, setEditedAmenagement] = React.useState<IAmenagement>();
   const { data: typesAmenagements } = useApi().useGetCollection(PREFETCH_TYPES_AMENAGEMENTS);
   const { data: categoriesAmenagements } = useApi().useGetCollection(
      PREFETCH_CATEGORIES_AMENAGEMENTS,
   );
   const { data: amenagements } = useApi().useGetCollectionPaginated({
      path: "/utilisateurs/{uid}/amenagements",
      parameters: {
         uid: props.utilisateurId,
      },
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
   });

   const amenagementsByCategories = useMemo(() => {
      return getAmenagementsByCategories(
         amenagements?.items || [],
         categoriesAmenagements?.items || [],
         typesAmenagements?.items || [],
         DOMAINES_AMENAGEMENTS_INFOS.examen,
      );
   }, [
      amenagements?.items,
      categoriesAmenagements?.items,
      DOMAINES_AMENAGEMENTS_INFOS.examen,
      typesAmenagements?.items,
   ]);

   const highlightAmenagement = searchParams.get("amenagement");

   return (
      <>
         <Flex justify="space-between" align="center" className="mt-1 mb-2" wrap>
            <Typography.Title level={3} className="mt-0 mb-0">
               Décision d'établissement
            </Typography.Title>
            <div className={`text-right${!screens.lg ? " mt-2" : ""}`}>
                  <BoutonDecisionEtab utilisateurId={props.utilisateurId} />
            </div>
         </Flex>

         <div>
            <div>
               <Row gutter={[16, 16]}>
                  {amenagementsByCategories?.map((c) => (
                     <span
                        key={c["@id"]}
                        style={{ width: "100%", display: "contents" }}
                     >
                     </span>
                  ))}
               </Row>
            </div>
            {amenagementsByCategories?.length === 0 && (
               <Empty className="mt-3 mb-1" description="Aucun aménagement" />
            )}
         </div>
      </>
   );
}

