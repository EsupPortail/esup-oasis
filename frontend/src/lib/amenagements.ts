/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IAmenagement, ICategorieAmenagement, ITypeAmenagement } from "../api/ApiTypeHelpers";

export type DomaineAmenagementInfos = {
   id: string;
   singulier: string;
   pluriel: string;
   libelleLongSingulier: string;
   libelleLongPluriel: string;
   visibleRenfort: boolean;
   visibleReferent: boolean;
   dateFinAmenagementObligatoire: boolean;
   couleur: string;
   order: number;
};

export enum AmenagementDomaine {
   pedagogique = "pedagogique",
   aideHumaine = "aideHumaine",
   examen = "examen",
}

export const DOMAINES_AMENAGEMENTS_INFOS: {
   [key: string]: DomaineAmenagementInfos;
} = {
   pedagogique: {
      id: AmenagementDomaine.pedagogique,
      singulier: "Pédagogique",
      pluriel: "Pédagogiques",
      libelleLongSingulier: "Aménagement pédagogique",
      libelleLongPluriel: "Aménagements pédagogiques",
      visibleRenfort: false,
      visibleReferent: true,
      dateFinAmenagementObligatoire: false,
      couleur: "purple",
      order: 3,
   },
   aideHumaine: {
      id: AmenagementDomaine.aideHumaine,
      singulier: "Aide humaine",
      pluriel: "Aides humaines",
      libelleLongSingulier: "Aide humaine",
      libelleLongPluriel: "Aides humaines",
      visibleRenfort: true,
      visibleReferent: false,
      dateFinAmenagementObligatoire: false,
      couleur: "yellow",
      order: 2,
   },
   examen: {
      id: AmenagementDomaine.examen,
      singulier: "Examen",
      pluriel: "Examens",
      libelleLongSingulier: "Aménagement d'examen",
      libelleLongPluriel: "Aménagements d'examen",
      visibleRenfort: false,
      visibleReferent: true,
      dateFinAmenagementObligatoire: true,
      couleur: "cyan",
      order: 1,
   },
};

export function getDomaineAmenagement(amenagement: ITypeAmenagement | undefined) {
   if (amenagement?.pedagogique) {
      return DOMAINES_AMENAGEMENTS_INFOS.pedagogique;
   }
   if (amenagement?.examens) {
      return DOMAINES_AMENAGEMENTS_INFOS.examen;
   }
   if (amenagement?.aideHumaine) {
      return DOMAINES_AMENAGEMENTS_INFOS.aideHumaine;
   }
   return null;
}

export function getTypesAmenagementByCategories(
   categories: ICategorieAmenagement[],
   typesAmenagements: ITypeAmenagement[],
   domaine?: string,
) {
   return categories
      .map((categorie) => {
         return {
            ...categorie,
            typesAmenagements: typesAmenagements
               .filter((ta) => ta.categorie === categorie["@id"])
               .filter((ta) => ta.actif)
               .filter((ta) => {
                  switch (domaine) {
                     case "pedagogique":
                        return ta.pedagogique;
                     case "aideHumaine":
                        return ta.aideHumaine;
                     case "examen":
                        return ta.examens;
                     default:
                        return true;
                  }
               }),
         };
      })
      .filter((c) => c.typesAmenagements.length > 0);
}

export function getAmenagementsByCategories(
   amenagements: IAmenagement[],
   categories: ICategorieAmenagement[],
   typesAmenagements: ITypeAmenagement[],
   domaine?: string,
) {
   return categories
      .map((categorie) => {
         return {
            ...categorie,
            typeAmenagements: typesAmenagements
               .filter((ta) => ta.categorie === categorie["@id"])
               .filter((ta) => {
                  switch (domaine) {
                     case "pedagogique":
                        return ta.pedagogique;
                     case "aideHumaine":
                        return ta.aideHumaine;
                     case "examen":
                        return ta.examens;
                     default:
                        return true;
                  }
               })
               .map((ta) => {
                  return {
                     ...ta,
                     amenagements: amenagements.filter((a) => a.typeAmenagement === ta["@id"]),
                  };
               })
               .filter((ta) => ta.amenagements.length > 0),
         };
      })
      .filter((c) => c.typeAmenagements.length > 0);
}

export function getAmenagementsDecision(
   amenagements: IAmenagement[],
   categories: ICategorieAmenagement[],
   typesAmenagements: ITypeAmenagement[],
) {
   return categories
      .map((categorie) => {
         return {
            ...categorie,
            typeAmenagements: typesAmenagements
               .filter((ta) => ta.categorie === categorie["@id"])
               .filter((ta) => ta.decision)
               .map((ta) => {
                  return {
                     ...ta,
                     amenagements: amenagements.filter((a) => a.typeAmenagement === ta["@id"]),
                  };
               })
               .filter((ta) => ta.amenagements.length > 0),
         };
      })
      .filter((c) => c.typeAmenagements.length > 0);
}
