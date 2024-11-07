/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { AnyAction } from "redux";
import { MODAL_EVENEMENT, MODAL_EVENEMENT_ID } from "../ReduxConstants";
import { IEvenement, IPartialEvenement } from "../../api/ApiTypeHelpers";

export const setModalEvenementId = (value: string | undefined): AnyAction => {
   return {
      type: MODAL_EVENEMENT_ID,
      payload: value,
   };
};

export const setModalEvenement = (value: IEvenement | IPartialEvenement | undefined): AnyAction => {
   return {
      type: MODAL_EVENEMENT,
      payload: value,
   };
};
