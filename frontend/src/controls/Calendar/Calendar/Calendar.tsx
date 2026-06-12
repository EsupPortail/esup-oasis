/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useCallback, useEffect, useMemo, useRef } from "react";
import "@controls/Calendar/Calendar/Calendar.scss";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin, { EventResizeDoneArg } from "@fullcalendar/interaction";
import {
  DateSelectArg,
  DayHeaderContentArg,
  EventClickArg,
  EventContentArg,
  EventDropArg,
} from "@fullcalendar/core";
import frLocale from "@fullcalendar/core/locales/fr";
import { getMonthHeader, getWeekHeader } from "@controls/Calendar/Calendar/utils";
import { CalendarEvenement, Evenement } from "@lib";
import CalendarEvent from "@controls/Calendar/Calendar/CalendarEvent";
import Spinner from "@controls/Spinner/Spinner";
import { canCreateEventOnDate, createDateFromStringAsUTC } from "@utils/dates";
import {
  DensiteValues,
  TypeAffichageCustomValues,
  useAffichageFiltres,
} from "@context/affichageFiltres/AffichageFiltresContext";
import { useAccessibilite } from "@context/accessibilite/AccessibiliteContext";
import { useEffectiveTheme } from "@utils/theme/useEffectiveTheme";
import { useModals } from "@context/modals/ModalsContext";
import { useApi } from "@context/api/ApiProvider";
import { useAuth } from "@/auth/AuthProvider";
import { ITypeEvenement, PREFETCH_LAST_PERIODES_RH, PREFETCH_TYPES_EVENEMENTS } from "@api";
import { App } from "antd";

function toFcView(type: TypeAffichageCustomValues): string {
  switch (type) {
    case "day":
      return "timeGridDay";
    case "week":
      return "timeGridWeek";
    case "work_week":
      return "timeGridWeek";
    case "month":
      return "dayGridMonth";
    default:
      return "timeGridWeek";
  }
}

interface ICalendar {
  events: Evenement[];
  setEvent: (event: Evenement) => void;
}

export default function Calendar({ events, setEvent }: ICalendar): ReactElement {
  const { message } = App.useApp();
  const user = useAuth().user;
  const calendarRef = useRef<FullCalendar>(null);
  const { setModalEvenementId, setModalEvenement } = useModals();
  const { accessibilite: appAccessibilite } = useAccessibilite();
  const isDark = useEffectiveTheme() === "dark";
  const { affichageFiltres: appAffichageFiltres, setAffichage } = useAffichageFiltres();
  const { data: typesEvenements, isFetching: isFetchingTypesEvenements } =
    useApi().useGetFullCollection(PREFETCH_TYPES_EVENEMENTS);

  const { data: lastPeriodes } = useApi().useGetCollection(PREFETCH_LAST_PERIODES_RH(user));

  // Sync de la date depuis le contexte (toolbar externe)
  useEffect(() => {
    const api = calendarRef.current?.getApi();
    if (api && appAffichageFiltres.filtres.debut) {
      api.gotoDate(appAffichageFiltres.filtres.debut);
    }
  }, [appAffichageFiltres.filtres.debut]);

  // Sync de la vue depuis le contexte (toolbar externe)
  useEffect(() => {
    const api = calendarRef.current?.getApi();
    if (api) {
      api.changeView(toFcView(appAffichageFiltres.affichage.type));
    }
  }, [appAffichageFiltres.affichage.type]);

  // region "Calendar event handlers"

  const handleEventChange = (event: CalendarEvenement, start: Date, end: Date): void => {
    if (
      !canCreateEventOnDate(start, user, lastPeriodes?.items[0]) ||
      !canCreateEventOnDate(new Date(event.data.debut), user, lastPeriodes?.items[0])
    ) {
      message
        .error(
          "Seuls les administrateurs sont autorisés à modifier des évènements dans les périodes de saisie antérieures.",
        )
        .then();
      return;
    }

    if (event.data.dateEnvoiRH) {
      message.error("L'évènement n'est pas modifiable car il a déjà été envoyé à la RH.").then();
      return;
    }

    setEvent(
      new Evenement({
        ...event.data,
        debut: createDateFromStringAsUTC(start.toISOString()).toISOString(),
        fin: createDateFromStringAsUTC(end.toISOString()).toISOString(),
      }),
    );
  };

  const handleEventCreate = (start: Date, end: Date): void => {
    if (!canCreateEventOnDate(start, user, lastPeriodes?.items[0])) {
      message
        .error(
          "Seuls les administrateurs sont autorisés à créer des évènements dans les périodes de saisie antérieures.",
        )
        .then();
      return;
    }

    setModalEvenement({
      debut: start.toISOString(),
      fin: end.toISOString(),
    });
  };

  // endregion

  const getEventStyle = useCallback(
    (eventData: Evenement) => {
      const typeEvenement = typesEvenements?.items.find(
        (t: ITypeEvenement) => t["@id"] === eventData.type,
      );
      const hasNote =
        (eventData.equipements || []).length > 0 ||
        eventData.dateAnnulation ||
        (!eventData.isAffecte() && !eventData.dateAnnulation);

      if (appAccessibilite.contrast) {
        return {
          className: `border-radius${eventData.dateAnnulation ? " event-annule" : ""}${hasNote ? " event-has-note" : ""}`,
          style: {
            fontSize:
              appAffichageFiltres.affichage.densite === DensiteValues.compact ? "0.8rem" : "1rem",
            backgroundColor: eventData.isAffecte()
              ? `var(--color-dark-${typeEvenement?.couleur})`
              : `var(--color-xlight-${typeEvenement?.couleur})`,
            color: eventData.isAffecte() ? "#FFF" : "#000",
            border: eventData.isAffecte() ? "none" : `5px solid var(--color-danger)`,
            backgroundImage: "",
            padding: appAffichageFiltres.affichage.type === "month" ? "0.2rem" : "0.5rem",
          },
        };
      }

      return {
        className: `border-radius${eventData.dateAnnulation ? " event-annule" : ""}${hasNote ? " event-has-note" : ""}`,
        style: {
          fontSize:
            appAffichageFiltres.affichage.densite === DensiteValues.compact ? "0.8rem" : "1rem",
          backgroundColor: eventData.isAffecte()
            ? `var(--color-${typeEvenement?.couleur})`
            : `var(--color-xlight-${typeEvenement?.couleur})`,
          color: `var(--color-dark-${typeEvenement?.couleur})`,
          border:
            !eventData.isAffecte() && isDark
              ? `2px dashed var(--color-${typeEvenement?.couleur})`
              : "none",
          backgroundImage: eventData.isAffecte() || isDark ? "" : "url(/images/strip.svg)",
          padding: appAffichageFiltres.affichage.type === "month" ? "0.2rem" : "0.33rem",
        },
      };
    },
    [
      typesEvenements?.items,
      appAccessibilite.contrast,
      isDark,
      appAffichageFiltres.affichage.densite,
      appAffichageFiltres.affichage.type,
    ],
  );

  function handleOpenModal(data: Evenement) {
    setModalEvenementId(data["@id"]);
  }

  const dayHeaderContent = useMemo(
    () => (arg: DayHeaderContentArg) => {
      const type = appAffichageFiltres.affichage.type;
      if (type === "month") return getMonthHeader(arg.date);
      if (type === "day") return <>{arg.date.toLocaleDateString()}</>;
      return getWeekHeader(arg.date, !canCreateEventOnDate(arg.date, user, lastPeriodes?.items[0]));
    },
    [appAffichageFiltres.affichage.type, user, lastPeriodes],
  );

  // Custom n'est valable que pour le layout Table
  useEffect(() => {
    if (appAffichageFiltres.affichage.type === "custom") {
      setAffichage({ type: "work_week" });
    }
  }, [appAffichageFiltres.affichage.type, setAffichage]);

  if (appAffichageFiltres.affichage.type === "custom") return <Spinner />;

  if (isFetchingTypesEvenements)
    return (
      <div data-testid="wait">
        <Spinner />
      </div>
    );

  return (
    <div
      className={`calendar density-${appAffichageFiltres.affichage.densite?.toLowerCase()} calendar-view-${
        appAffichageFiltres.affichage.type
      } ${appAffichageFiltres.affichage.fitToScreen ? "fit-to-screen" : ""}`}
    >
      <FullCalendar
        ref={calendarRef}
        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
        initialView={toFcView(appAffichageFiltres.affichage.type)}
        locale={frLocale}
        headerToolbar={false}
        weekends={appAffichageFiltres.affichage.type !== "work_week"}
        allDaySlot={false}
        slotMinTime="07:00:00"
        slotMaxTime="22:00:00"
        slotDuration="00:15:00"
        slotLabelInterval="00:30:00"
        stickyHeaderDates={true}
        height="auto"
        events={events.map((event) => event.toFcEvent())}
        eventContent={(arg: EventContentArg) => {
          const eventData = arg.event.extendedProps.data as Evenement;
          const { style, className } = getEventStyle(eventData);
          const calEvent: CalendarEvenement = {
            title: arg.event.title,
            start: arg.event.start ?? undefined,
            end: arg.event.end ?? undefined,
            allDay: arg.event.allDay,
            data: eventData,
          };
          return (
            <div
              style={{ ...style, height: "100%", width: "100%", overflow: "hidden" }}
              className={className}
            >
              <CalendarEvent key={calEvent.data.hashCode()} event={calEvent} />
            </div>
          );
        }}
        dayHeaderContent={dayHeaderContent}
        selectable={user?.isPlanificateur}
        editable={user?.isPlanificateur || false}
        eventDrop={(arg: EventDropArg) => {
          if (user?.isPlanificateur && arg.event.start && arg.event.end) {
            const calEvent: CalendarEvenement = {
              title: arg.event.title,
              start: arg.event.start,
              end: arg.event.end,
              allDay: arg.event.allDay,
              data: arg.event.extendedProps.data as Evenement,
            };
            handleEventChange(calEvent, arg.event.start, arg.event.end);
          }
        }}
        eventResize={(arg: EventResizeDoneArg) => {
          if (user?.isPlanificateur && arg.event.start && arg.event.end) {
            const calEvent: CalendarEvenement = {
              title: arg.event.title,
              start: arg.event.start,
              end: arg.event.end,
              allDay: arg.event.allDay,
              data: arg.event.extendedProps.data as Evenement,
            };
            handleEventChange(calEvent, arg.event.start, arg.event.end);
          }
        }}
        select={(arg: DateSelectArg) => {
          if (user?.isPlanificateur) handleEventCreate(arg.start, arg.end);
        }}
        eventClick={(arg: EventClickArg) => {
          handleOpenModal(arg.event.extendedProps.data as Evenement);
        }}
      />
    </div>
  );
}
