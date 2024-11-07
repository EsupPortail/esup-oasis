/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { AnyAction } from "redux";
import {
   ACCESSIBILITE_CONTRAST,
   ACCESSIBILITE_DYSLEXIE_ARIAL,
   ACCESSIBILITE_DYSLEXIE_OPENDYS,
   ACCESSIBILITE_POLICE_LARGE,
} from "../ReduxConstants";
import { IAccessibilite, initialAccessibilite } from "../context/IAccessibilite";

export const AccessibiliteReducer = (
   state = initialAccessibilite,
   action: AnyAction | undefined = undefined,
): IAccessibilite => {
   switch (action?.type) {
      case ACCESSIBILITE_CONTRAST:
         return {
            ...{
               ...state,
               contrast: action.payload,
            },
         };

      case ACCESSIBILITE_DYSLEXIE_ARIAL:
         return {
            ...state,
            dyslexieArial: action.payload,
            dyslexieOpenDys: action.payload ? false : state.dyslexieOpenDys,
         };

      case ACCESSIBILITE_DYSLEXIE_OPENDYS:
         return {
            ...state,
            dyslexieOpenDys: action.payload,
            dyslexieArial: action.payload ? false : state.dyslexieArial,
         };

      case ACCESSIBILITE_POLICE_LARGE:
         return {
            ...state,
            policeLarge: action.payload,
         };

      default:
         return state;
   }
};
