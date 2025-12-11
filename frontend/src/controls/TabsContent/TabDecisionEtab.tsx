/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { getAmenagementsDecision } from "../../lib/amenagements";
import React, { useMemo } from "react";
import { ITypeAmenagement } from "../../api/ApiTypeHelpers";
import { useApi } from "../../context/api/ApiProvider";
import {
   PREFETCH_CATEGORIES_AMENAGEMENTS,
   PREFETCH_TYPES_AMENAGEMENTS,
} from "../../api/ApiPrefetchHelpers";
import { CardAmenagement } from "../Card/CardAmenagement";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { Empty, Flex, Row, Typography } from "antd";

import { useSearchParams } from "react-router-dom";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { BoutonDecisionEtab } from "./BoutonDecisionEtab";
import { getDomaineAmenagement } from "../../lib/amenagements";

export function TabDecisionEtab(props: {
   utilisateurId: string;
}) {
   const screens = useBreakpoint();
   const [searchParams] = useSearchParams();
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

   const amenagementsDecision = useMemo(() => {
      return getAmenagementsDecision(
         amenagements?.items || [],
         categoriesAmenagements?.items || [],
         typesAmenagements?.items || [],
      );
   }, [
      amenagements?.items,
      categoriesAmenagements?.items,
      typesAmenagements?.items,
   ]);

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
                  {amenagementsDecision?.map((c) => (
                     <span
                        key={c["@id"]}
                        style={{ width: "100%", display: "contents" }}
                     >
                        {c.typeAmenagements.map((ta) => (
                           <span key={ta["@id"]} style={{ width: "100%", display: "contents" }}>
                              {ta.amenagements.map((a) => (
                                 <CardAmenagement
                                    couleur={getDomaineAmenagement(ta as ITypeAmenagement)?.couleur} 
                                    categorie={c.libelle!} 
                                    amenagement={a} 
                                    type={ta.libelle}>
                                 </CardAmenagement>
                              ))}
                           </span>
                        ))}
                     </span>
                  ))}
               </Row>
            </div>
            {amenagementsDecision?.length === 0 && (
               <Empty className="mt-3 mb-1" description="Aucun aménagement" />
            )}
         </div>
      </>
   );
}
