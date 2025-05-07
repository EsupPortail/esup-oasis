/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { useApi } from "../../context/api/ApiProvider";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { Skeleton, Tag, Tooltip } from "antd";

interface IItemProfilProps {
   profil?: string;
   profils?: string[];
   maxWidth?: string | number;
   className?: string;
}

/**
 * Renders a list of profil items.
 *
 * @param {Object} props - The props for the ProfilItem component.
 * @param {string} [props.profil] - The selected profil. If provided, only this profil will be displayed.
 * @param {string[]} [props.profils] - The list of profils. If provided, all profils will be displayed.
 *
 * @returns {ReactElement} - The rendered list of profil items.
 */
export default function ProfilItem({ profil, profils, maxWidth, className }: IItemProfilProps): ReactElement {
   const data = profil ? [profil] : profils;
   const { data: dataProfils, isFetching: isFetchingProfils } = useApi().useGetCollectionPaginated({
      path: "/profils",
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
   });

   if (isFetchingProfils || !data) return <Skeleton.Input className="mb-05" active />;

   return (
      <>
         {data.map((s) => {
            const p = dataProfils?.items.find((ob) => ob["@id"] === s);
            return (
               <Tooltip key={s} title={p?.libelle}>
                  <Tag
                     className={(maxWidth ? "mt-1 tag-ellipsis" : "mt-1") + (className ? " " + className : "")}
                     style={{ maxWidth: maxWidth }}
                  >
                     {p?.libelle}
                  </Tag>
               </Tooltip>
            );
         })}
      </>
   );
}
