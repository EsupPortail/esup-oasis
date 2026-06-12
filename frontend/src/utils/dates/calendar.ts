/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import dayjs from "dayjs";
import { Utilisateur } from "@lib";
import { IPeriode } from "@api";

export enum TypeOperationHeure {
  Inchangee = 0,
  debutJournee = 1,
  finJournee = 2,
}

/**
 * Creates a new Date object that represents the given date in UTC timezone.
 * Allow to set the hour to the beginning or the end of the day.
 *
 * @param {Date} date - The date to convert to UTC.
 * @param {TypeOperationHeure} [heure=TypeOperationHeure.Inchangee] - The hour type.
 * @return {Date} A new Date object representing the given date in UTC timezone.
 */
export function createDateAsUTC(
  date: Date,
  heure: TypeOperationHeure = TypeOperationHeure.Inchangee,
): Date {
  const d = new Date(date);
  if (heure === TypeOperationHeure.debutJournee) {
    d.setHours(0, 0, 0, 0);
  }
  if (heure === TypeOperationHeure.finJournee) {
    d.setHours(23, 59, 59, 999);
  }

  return new Date(
    Date.UTC(
      d.getFullYear(),
      d.getMonth(),
      d.getDate(),
      d.getHours(),
      d.getMinutes(),
      d.getSeconds(),
    ),
  );
}

/**
 * Create a Date object in UTC timezone from a string representation.
 *
 * @param {string} date - The string representation of the date.
 * @param {TypeOperationHeure} heure - The type of operation to perform on the hour. Default value is TypeOperationHeure.Inchangee.
 * @return {Date} - The Date object in UTC timezone.
 */
export function createDateFromStringAsUTC(
  date: string,
  heure: TypeOperationHeure = TypeOperationHeure.Inchangee,
): Date {
  return createDateAsUTC(new Date(date), heure);
}

/**
 * Checks if a given value contains a valid date.
 *
 * @param {string | undefined} val - The value to check.
 * @return {boolean} - True if the value contains a valid date, false otherwise.
 */
export function isDateValid(val: string | undefined): boolean {
  if (!val) return false;
  const date = new Date(val);
  return !isNaN(date.getTime());
}

/**
 * Determines if an event can be created on a given date.
 *
 * @param {Date} date - The date to check for event creation.
 * @param {Utilisateur | undefined} user - The user's information.
 * @param {IPeriode | undefined} lastPeriode - The last period information.
 *
 * @returns {boolean} - True if the event can be created, false otherwise.
 */
export function canCreateEventOnDate(
  date: Date,
  user: Utilisateur | undefined,
  lastPeriode: IPeriode | undefined,
): boolean {
  if (!user) return false;

  // Admin peut tout faire
  if (user.isAdmin) return true;

  //Pas de période précédente
  if (!lastPeriode) return true;

  return dayjs(lastPeriode.butoir).endOf("day").isBefore(dayjs(date).startOf("day"));
}
