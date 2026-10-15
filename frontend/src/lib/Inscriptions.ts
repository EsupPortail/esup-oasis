/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IComposante, IInscription } from "@api";
import dayjs from "dayjs";

export function composantesFromInscriptions(
  inscriptions: IInscription[],
  composantes: IComposante[],
): IComposante[] {
  return (inscriptions || [])
    .map((inscription) => inscription.formation)
    .map((formation) => formation?.composante)
    .map((composante) => {
      if (!composante) return null;
      return composantes?.find((c) => c["@id"] === composante);
    })
    .filter(
      (composante): composante is IComposante => composante !== null && composante !== undefined,
    );
}

export function inscriptionsActives(inscriptions?: IInscription[]): IInscription[] {
  return (inscriptions || []).filter((inscription) => dayjs(inscription.fin).isAfter());
}
