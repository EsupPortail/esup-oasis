/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useEffect, useMemo, useState } from "react";
import { App, Layout } from "antd";
import CalendarSider from "./Sider/CalendarSider";
import Calendar from "./Calendar/Calendar";
import Toolbar from "./Toolbar/Toolbar";
import { Evenement } from "../../lib/Evenement";
import {
   filtrerEvenements,
   filtreToApi,
   IAffichageFiltres,
   PlanningLayout,
} from "../../redux/context/IAffichageFiltres";
import { IStore } from "../../redux/Store";
import { useDispatch, useSelector } from "react-redux";
import { setFiltres } from "../../redux/actions/AffichageFiltre";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { useApi } from "../../context/api/ApiProvider";
import Spinner from "../Spinner/Spinner";
import { calculateRange } from "../../utils/dates";
import { PREFETCH_TYPES_EVENEMENTS } from "../../api/ApiPrefetchHelpers";
import CalendarTable from "./Table/CalendarTable";
import { ApiPathMethodQuery } from "../../api/SchemaHelpers";
import { IEvenement } from "../../api/ApiTypeHelpers";
import { TimezoneAlert } from "./TimezoneAlert";

interface IComponentWithSider {
   saisieEvtRenfort?: boolean;
}

/**
 * A component for planning events with a sider.
 *
 * @param {IComponentWithSider} params - The parameters for the component.
 * @param {boolean} [params.saisieEvtRenfort=false] - Whether to allow entering reinforcement events.
 * @returns {ReactElement} - The rendered component.
 */
export default function PlanningWithSider({
   saisieEvtRenfort = false,
}: IComponentWithSider): ReactElement {
   const { message } = App.useApp();

   const appAffichageFiltres: IAffichageFiltres = useSelector(
      ({ affichageFiltres }: IStore) => affichageFiltres,
   );
   const dispatch = useDispatch();
   const [events, setEvents] = useState<Evenement[]>([]);

   // Get /types_evenements
   const { data: typesEvenements } = useApi().useGetCollection(PREFETCH_TYPES_EVENEMENTS);

   // GET /evenements
   const { data: evenements, isFetching } = useApi().useGetCollectionPaginated({
      path: "/evenements",
      query: filtreToApi(appAffichageFiltres.filtres) as ApiPathMethodQuery<"/evenements">,
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
   });

   // Mutation d'un évènement
   const mutateEvenement = useApi().usePatch({
      path: "/evenements/{id}",
      invalidationQueryKeys: ["/evenements", "/statistiques_evenements"],
      onSuccess: () => {
         message.success("Évènement replanifié").then();
      },
   });

   // Conversion des évènements en Evenement
   useEffect(() => {
      setEvents(evenements?.items.map((e: IEvenement) => new Evenement(e)) ?? []);
   }, [evenements]);

   // Persist de l'évènement modifié
   const setEvent = (event: Evenement) => {
      mutateEvenement?.mutate({
         "@id": event["@id"] as string,
         data: event,
      });
   };

   useEffect(() => {
      if (!appAffichageFiltres.filtres.type || appAffichageFiltres.filtres.type.length === 0) {
         dispatch(
            setFiltres({
               type: typesEvenements?.items
                  .filter((t) => t.visibleParDefaut)
                  .filter((t) => t.actif)
                  .map((t) => t["@id"] as string),
            }),
         );
      }
      // eslint-disable-next-line react-hooks/exhaustive-deps
   }, [typesEvenements]);

   // Ajustement de la plage de dates / affichage
   useEffect(() => {
      const range = calculateRange(
         appAffichageFiltres.filtres.debut,
         appAffichageFiltres.affichage.type,
      );
      dispatch(setFiltres({ debut: range.from, fin: range.to }));
      // eslint-disable-next-line react-hooks/exhaustive-deps
   }, [appAffichageFiltres.affichage.type]);

   const eventsFiltres = useMemo(() => {
      return filtrerEvenements(events, appAffichageFiltres.filtres);
   }, [events, appAffichageFiltres.filtres]);

   return (
      <Layout>
         <h1 className="sr-only">Planning</h1>
         <CalendarSider saisieEvtRenfort={saisieEvtRenfort} />
         <Layout.Content className="calendar-table-content">
            <Toolbar saisieEvtRenfort={saisieEvtRenfort} evenements={events} />
            <TimezoneAlert />
            {isFetching && (
               <div
                  className="d-flex-center"
                  style={{
                     position: "fixed",
                     top: 80,
                     zIndex: 100,
                     width: "100%",
                     height: "calc(100vh - 80px)",
                     backgroundColor: "#FFFFFFAA",
                  }}
               >
                  <Spinner size={100} />
               </div>
            )}
            {appAffichageFiltres.affichage.layout === PlanningLayout.calendar ? (
               <Calendar events={eventsFiltres} setEvent={setEvent} />
            ) : (
               <CalendarTable events={eventsFiltres} saisieEvtRenfort={saisieEvtRenfort} />
            )}
         </Layout.Content>
      </Layout>
   );
}
