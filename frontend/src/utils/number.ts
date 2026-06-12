/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

export function montantToString(
  nbHeures?: string,
  tauxHoraire?: string,
  coefCoutCharge?: string,
): string {
  let montant = parseFloat(nbHeures || "0") * parseFloat(tauxHoraire || "0");
  if (coefCoutCharge) {
    montant = montant * parseFloat(coefCoutCharge);
  }
  if (Number.isNaN(montant)) return "—";
  return montant.toFixed(2).toString().replace(".", ",");
}

export function to2Digits(value?: string | number, defaultValue = "0.00") {
  if (value === undefined) {
    return defaultValue;
  }
  const parsed = parseFloat(value.toString());
  if (Number.isNaN(parsed)) return "—";
  return parsed.toFixed(2).replace(".", ",");
}
