/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useEffect } from "react";
import { Input, Skeleton, Space } from "antd";
import dayjs from "dayjs";
import { useApi } from "../../context/api/ApiProvider";
import { PREFETCH_TYPES_EVENEMENTS } from "../../api/ApiPrefetchHelpers";
import { IEvenement } from "../../api/ApiTypeHelpers";
import { montantToString } from "../../utils/number";

interface ITarifEvenementFieldProps {
   as?: "input" | "text";
   evenement: IEvenement;
}

export default function TarifEvenementField({
   evenement,
   as = "input",
}: ITarifEvenementFieldProps) {
   const { data: typesEvenements, isFetching: isFetchingTypeEvenement } =
      useApi().useGetCollection(PREFETCH_TYPES_EVENEMENTS);
   const [dureeTotale, setDureeTotale] = React.useState<number>(0);

   const { data: taux } = useApi().useGetCollection({
      path: "/types_evenements/{typeId}/taux",
      parameters: {
         typeId: evenement.type as string,
      },
      query: {
         date: dayjs(evenement.debut).format("YYYY-MM-DD"),
      },
      enabled:
         !!typesEvenements &&
         !!evenement.type &&
         !!typesEvenements?.items.find((t) => t["@id"] === evenement.type)?.tauxActif,
   });

   useEffect(() => {
      // get nb minutes between evenement.debut and evenement.fin
      const nbMinutes = dayjs(evenement.fin).diff(dayjs(evenement.debut), "minute");
      setDureeTotale(
         nbMinutes + (evenement.tempsPreparation || 0) + (evenement.tempsSupplementaire || 0),
      );
   }, [evenement.tempsPreparation, evenement.tempsSupplementaire, evenement.debut, evenement.fin]);

   if (isFetchingTypeEvenement) {
      return <Skeleton active />;
   }

   if (!taux || taux.items.length !== 1 || !taux.items[0].montant) {
      return (
         <p className="text-warning">
            Pas de taux horaire défini actuellement pour les évènements de la catégorie "
            {typesEvenements?.items.find((t) => t["@id"] === evenement.type)?.libelle}"
         </p>
      );
   }

   if (as === "input") {
      return (
         <>
            <Input
               disabled
               addonAfter={<div style={{ width: 55 }}>€</div>}
               placeholder="0,00"
               className="text-center text-primary semi-bold"
               value={montantToString((dureeTotale / 60).toString(), taux.items[0].montant)}
            />
            <div className="legende">
               Tarif horaire des évènements de la catégorie "
               {typesEvenements?.items.find((t) => t["@id"] === evenement.type)?.libelle}"&nbsp;:{" "}
               <span className="no-wrap">
                  {taux.items[0].montant || "?"}
                  &nbsp;€ brut / heure
               </span>
            </div>
         </>
      );
   }

   return (
      <Space>
         <span>{montantToString((dureeTotale / 60).toString(), taux.items[0].montant)}</span>
         <span>€</span>
      </Space>
   );
}
