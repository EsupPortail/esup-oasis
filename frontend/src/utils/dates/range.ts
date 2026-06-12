/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import dayjs from "dayjs";
import isBetween from "dayjs/plugin/isBetween";
import { TypeAffichageCustomValues } from "@context/affichageFiltres/AffichageFiltresContext";

dayjs.extend(isBetween);

/**
 * Counts the number of days in a month for a given date.
 *
 * @param {Date} date - The date for which to count the number of days in the month.
 * @return {number} - The number of days in the month of the given date.
 */
export function countDaysInMonth(date: Date): number {
  return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
}

/**
 * Function to get the first day of the month from a given date.
 *
 * @param {Date} date - The input date.
 * @return {Date} The first day of the month.
 */
export function firstDayOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1);
}

/**
 * Get the last day of the month for a given date.
 *
 * @param {Date} date - The input date.
 * @return {Date} - The last day of the month.
 */
export function lastDayOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth() + 1, 0);
}

/**
 * Finds the first Monday before the given date.
 *
 * @param {Date} date - The date from which to find the first Monday before.
 * @return {Date} - The first Monday before the given date.
 */
export function firstMondayBefore(date: Date): Date {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  return new Date(d.setDate(diff));
}

/**
 * Calculates the first Sunday date after the given date.
 *
 * @param {Date} date - The starting date.
 * @returns {Date} The first Sunday date after the given date.
 */
export function firstSundayAfter(date: Date): Date {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? 0 : 7);
  return new Date(d.setDate(diff));
}

/**
 * Returns the date of the first Friday after the given date.
 *
 * @param {Date} date - The starting date.
 * @return {Date} - The date of the first Friday after the given date.
 */
export function firstFridayAfter(date: Date): Date {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + 5;
  return new Date(d.setDate(diff));
}

/**
 * Checks if two dates are the same day, ignoring the time.
 *
 * @param {Date} date1 - The first date to compare.
 * @param {Date} date2 - The second date to compare.
 * @return {boolean} - True if the dates are the same day, otherwise false.
 */
export function isSameDay(date1: Date, date2: Date): boolean {
  return (
    date1.getDate() === date2.getDate() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getFullYear() === date2.getFullYear()
  );
}

/**
 * Calculates the range of dates based on the given start date and display type.
 *
 * @param {Date} debut - The start date of the range.
 * @param {string} affichage - The display type for the range. Possible values are "day", "work_week", "week", and "month".
 * @returns {{from: Date, to: Date}} - An object containing the calculated "from" and "to" dates.
 */
export const calculateRange = (
  debut: Date,
  affichage: TypeAffichageCustomValues,
): { from: Date; to: Date } => {
  let from = debut;
  let to: Date;

  switch (affichage) {
    case "day":
      to = new Date(from);
      break;
    case "work_week":
      from = firstMondayBefore(debut);
      to = firstFridayAfter(debut);
      break;
    case "week":
      from = firstMondayBefore(debut);
      to = firstSundayAfter(debut);
      break;
    case "month":
    default:
      from = firstDayOfMonth(debut);
      to = lastDayOfMonth(debut);
      break;
  }

  return { from, to };
};

/**
 * Counts the number of days based on the given affichage and date.
 *
 * @param {TypeAffichageCustomValues} affichage - The type of affichage (month, week, work_week, day)
 * @param {Date} date - The date for which to count the days.
 * @returns {number} - The number of days according to the affichage.
 */
export const affichageNbJours = (affichage: TypeAffichageCustomValues, date: Date): number => {
  let step = 0;
  switch (affichage) {
    case "month":
      step = countDaysInMonth(date);
      break;
    case "week":
    case "work_week":
      step = 7;
      break;
    case "day":
      step = 1;
      break;
  }
  return step;
};

/**
 * Checks if the current date is within the specified period.
 *
 * @param {string | undefined | null} debut - The start of the period.
 * @param {string | undefined | null} fin - The end of the period.
 * @returns {boolean} - Returns true if the current date is within the specified period, otherwise false.
 */
export function isEnCoursSurPeriode(
  debut: string | undefined | null,
  fin: string | undefined | null,
): boolean {
  if (!debut && !fin) return false;

  if (!debut) {
    return dayjs().isBefore(fin);
  }

  if (!fin) {
    return dayjs().isAfter(debut);
  }

  const dateDebut = new Date(debut);
  const dateFin = new Date(fin);

  return dayjs().isBetween(dateDebut, dateFin);
}
