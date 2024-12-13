/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useEffect, useState } from "react";
import { IAmenagementBeneficiaireQuery, IAmenagementQuery } from "../../api/ApiTypeHelpers";
import { Button, Flex, Tooltip } from "antd";
import { useApi } from "../../context/api/ApiProvider";
import { AmenagementTableFilter } from "./AmenagementTableFilter";
import { AmenagementDomaine } from "../../lib/amenagements";
import {
   PREFETCH_CATEGORIES_AMENAGEMENTS,
   PREFETCH_TYPES_AMENAGEMENTS,
} from "../../api/ApiPrefetchHelpers";
import Icon from "@ant-design/icons";
import { ReactComponent as Unfilter } from "../../assets/images/unfilter.svg";
import { useAuth } from "../../auth/AuthProvider";
import { ModeAffichageAmenagement } from "../../routes/gestionnaire/beneficiaires/Amenagements";
import AmenagementTableExport from "./AmenagementTableExport";
import { AmenagementTable } from "./AmenagementTable";
import { AmenagementsBeneficiaireTable } from "./AmenagementsBeneficiaireTable";
import AmenagementsBeneficiaireTableExport from "./AmenagementsBeneficiaireTableExport";
import FiltreDescription from "./FiltreDescription";
import { Utilisateur } from "../../lib/Utilisateur";
import { usePreferences } from "../../context/utilisateurPreferences/UtilisateurPreferencesProvider";
import { getCountLibelle } from "../../utils/table";

export interface FiltreAmenagement {
   "tags[]"?: string[];
   "type[]"?: string[];
   "categorie[]"?: string[];
   "suivis[]"?: string[];
   domaine?: AmenagementDomaine | "Tous";
   page: number;
   itemsPerPage: number;
   "composante[]"?: string[];
   "formation[]"?: string[];
   "order[nom]"?: "asc" | "desc" | undefined;
   "order[beneficiaires.utilisateur.nom]"?: "asc" | "desc" | undefined;
   avisEse?: string;
   restreindreColonnes?: boolean;
   nom?: string;
   "beneficiaires.utilisateur.nom"?: string;
   etatAvisEse?: string;
   "gestionnaire[]"?: string[];
}

const FILTRE_AMENAGEMENT_DEFAULT: FiltreAmenagement = {
   domaine: "Tous",
   itemsPerPage: 25,
   page: 1,
   "order[nom]": "asc",
   "order[beneficiaires.utilisateur.nom]": "asc",
   restreindreColonnes: false,
};

type FiltreInApi = (IAmenagementQuery | IAmenagementBeneficiaireQuery) & {
   "order[nom]"?: "asc" | "desc";
   "order[beneficiaires.utilisateur.nom]"?: "asc" | "desc";
};

export function getFiltreAmenagementDefault(user: Utilisateur): FiltreAmenagement {
   if (user.isRenfort && !user.isGestionnaire) {
      return {
         ...FILTRE_AMENAGEMENT_DEFAULT,
         domaine: AmenagementDomaine.aideHumaine,
         restreindreColonnes: true,
      };
   }

   return FILTRE_AMENAGEMENT_DEFAULT;
}

export function filtreAmenagementToApi(
   filtre: FiltreAmenagement,
   modeAffichage: ModeAffichageAmenagement,
): FiltreInApi {
   let res: FiltreInApi = {
      page: filtre.page,
      itemsPerPage: filtre.itemsPerPage,

      // Catégories
      "type.categorie[]": filtre["categorie[]"],
      "categorie[]": filtre["categorie[]"],

      // Autres
      "type[]": filtre["type[]"],
      "composante[]": filtre["composante[]"],
      "formation[]": filtre["formation[]"],
      "tags[]": filtre["tags[]"],
      "gestionnaire[]": filtre["gestionnaire[]"],
      nom: filtre.nom,
      etatAvisEse: filtre.etatAvisEse,
   };

   if (modeAffichage === ModeAffichageAmenagement.ParBeneficiaire) {
      res = {
         ...res,
         examens: filtre.domaine === AmenagementDomaine.examen ? true : undefined,
         pedagogique: filtre.domaine === AmenagementDomaine.pedagogique ? true : undefined,
         aideHumaine: filtre.domaine === AmenagementDomaine.aideHumaine ? true : undefined,
         "order[nom]": filtre["order[nom]"] ?? "asc",
      };
   } else {
      res = {
         ...res,
         "type.aideHumaine": filtre.domaine === AmenagementDomaine.aideHumaine ? true : undefined,
         "type.pedagogique": filtre.domaine === AmenagementDomaine.pedagogique ? true : undefined,
         "type.examens": filtre.domaine === AmenagementDomaine.examen ? true : undefined,
         "suivi[]": filtre["suivis[]"],
         "order[beneficiaires.utilisateur.nom]": filtre["order[beneficiaires.utilisateur.nom]"],
      };
   }

   return res;
}

export default function AmenagementTableLayout(props: { modeAffichage: ModeAffichageAmenagement }) {
   const auth = useAuth();
   const { getPreferenceArray, preferencesChargees } = usePreferences();
   const [count, setCount] = React.useState<number>();
   const [filtreAmenagement, setFiltreAmenagement] = useState<FiltreAmenagement>({
      ...getFiltreAmenagementDefault(auth.user as Utilisateur),
      // on applique le filtre favori des préférences de l'utilisateur s'il existe
      ...{
         ...getPreferenceArray(
            props.modeAffichage === "amenagement"
               ? "filtresAmenagement"
               : "filtresAmenagementParBeneficiaire",
         )?.filter((f) => f.favori)[0]?.filtre,
         page: 1,
      },
   });
   const { data: categoriesAmenagements } = useApi().useGetCollection(
      PREFETCH_CATEGORIES_AMENAGEMENTS,
   );
   const { data: typesAmenagements } = useApi().useGetCollection(PREFETCH_TYPES_AMENAGEMENTS);

   useEffect(() => {
      setFiltreAmenagement({
         ...getFiltreAmenagementDefault(auth.user as Utilisateur),
         // on applique le filtre favori des préférences de l'utilisateur s'il existe
         ...{
            ...getPreferenceArray(
               props.modeAffichage === "amenagement"
                  ? "filtresAmenagement"
                  : "filtresAmenagementParBeneficiaire",
            )?.filter((f) => f.favori)[0]?.filtre,
            page: 1,
         },
      });
   }, [props.modeAffichage, auth.user, getPreferenceArray]);

   useEffect(() => {
      if (preferencesChargees) {
         setFiltreAmenagement({
            ...getFiltreAmenagementDefault(auth.user as Utilisateur),
            // on applique le filtre favori des préférences de l'utilisateur s'il existe
            ...getPreferenceArray(
               props.modeAffichage === "amenagement"
                  ? "filtresAmenagement"
                  : "filtresAmenagementParBeneficiaire",
            )?.filter((f) => f.favori)[0]?.filtre,
         });
      }
      // eslint-disable-next-line react-hooks/exhaustive-deps
   }, [preferencesChargees]);

   return (
      <>
         <AmenagementTableFilter
            filtreAmenagement={filtreAmenagement}
            setFiltreAmenagement={setFiltreAmenagement}
            modeAffichage={props.modeAffichage}
         />
         <Flex className="w-100" justify="space-between" align="center">
            <span className="legende">
               {getCountLibelle(
                  count,
                  props.modeAffichage === ModeAffichageAmenagement.ParAmenagement
                     ? "aménagement"
                     : "bénéficiaire",
               )}
            </span>

            <div className="d-block">
               {JSON.stringify(getFiltreAmenagementDefault(auth.user as Utilisateur)) !==
                  JSON.stringify(filtreAmenagement) && (
                  <Button.Group>
                     <FiltreDescription
                        filtre={filtreAmenagement}
                        as="modal"
                        tooltip="Décrire le filtre en cours"
                     />
                     <Tooltip title="Retirer les filtres">
                        <Button
                           className="d-flex-inline-center mr-1"
                           icon={<Icon component={Unfilter} aria-label="Retirer les filtres" />}
                           onClick={() =>
                              setFiltreAmenagement(
                                 getFiltreAmenagementDefault(auth.user as Utilisateur),
                              )
                           }
                        />
                     </Tooltip>
                  </Button.Group>
               )}
               {auth.user?.isGestionnaire &&
                  props.modeAffichage === ModeAffichageAmenagement.ParAmenagement && (
                     <AmenagementTableExport filtreAmenagement={filtreAmenagement} />
                  )}
               {(auth.user?.isGestionnaire || auth.user?.isReferentComposante) &&
                  props.modeAffichage === ModeAffichageAmenagement.ParBeneficiaire && (
                     <AmenagementsBeneficiaireTableExport
                        filtreAmenagement={filtreAmenagement}
                        typesAmenagements={typesAmenagements?.items || []}
                     />
                  )}
            </div>
         </Flex>
         {props.modeAffichage === ModeAffichageAmenagement.ParAmenagement ? (
            <AmenagementTable
               filtreAmenagement={filtreAmenagement}
               setFiltreAmenagement={setFiltreAmenagement}
               typesAmenagements={typesAmenagements?.items}
               categoriesAmenagements={categoriesAmenagements?.items}
               setCount={setCount}
            />
         ) : (
            <AmenagementsBeneficiaireTable
               filtreAmenagement={filtreAmenagement}
               setFiltreAmenagement={setFiltreAmenagement}
               typesAmenagements={typesAmenagements?.items}
               categoriesAmenagements={categoriesAmenagements?.items}
               setCount={setCount}
            />
         )}
      </>
   );
}
