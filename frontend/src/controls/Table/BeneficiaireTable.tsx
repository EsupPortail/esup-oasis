/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useEffect, useState } from "react";
import { IBeneficiaire } from "../../api/ApiTypeHelpers";
import { beneficiaireTableColumns } from "./BeneficiaireTableColumns";
import { setDrawerUtilisateur } from "../../redux/actions/Drawers";
import { RoleValues } from "../../lib/Utilisateur";
import { Button, Flex, Table, Tooltip } from "antd";
import Icon from "@ant-design/icons";
import { useApi } from "../../context/api/ApiProvider";
import { useAuth } from "../../auth/AuthProvider";
import { useDispatch } from "react-redux";
import BeneficiaireTableExport from "./BeneficiaireTableExport";
import { SorterResult } from "antd/es/table/interface";
import { setAffichageFiltres } from "../../redux/actions/AffichageFiltre";
import { initialAffichageFiltres } from "../../redux/context/IAffichageFiltres";
import { queryClient } from "../../App";
import { useNavigate, useSearchParams } from "react-router-dom";
import { ReactComponent as Unfilter } from "../../assets/images/unfilter.svg";
import { BeneficiaireTableFilter } from "./BeneficiaireTableFilter";
import { ascendToAsc } from "../../utils/array";
import FiltreDescription from "./FiltreDescription";
import { usePreferences } from "../../context/utilisateurPreferences/UtilisateurPreferencesProvider";
import { getCountLibelle } from "../../utils/table";

export const FILTRE_BENEFICIAIRE_DEFAULT = {
   "order[nom]": "asc" as "asc" | "desc" | undefined,
   "beneficiaires.avecAccompagnement": true,
   page: 1,
   itemsPerPage: 25,
};

export interface FiltreBeneficiaire {
   profil?: string;
   nom?: string;
   prenom?: string;
   nomGestionnaire?: string;
   "gestionnaire[]"?: string[];
   "order[nom]"?: "asc" | "desc" | undefined;
   "beneficiaires.avecAccompagnement"?: boolean | undefined;
   "composante[]"?: string[];
   "formation[]"?: string[];
   page: number;
   itemsPerPage: number;
   "tags[]"?: string[];
   etatAvisEse?: string;
   etatDecisionAmenagement?: string;
}

function filtreBeneficiaireDefault(
   filtreType: string | null,
   filtreValeur: string | null,
): FiltreBeneficiaire {
   switch (filtreType) {
      case "etatDecisionAmenagement":
         return {
            ...FILTRE_BENEFICIAIRE_DEFAULT,
            etatDecisionAmenagement: filtreValeur as string,
         };
      case "etatAvisEse":
         return {
            ...FILTRE_BENEFICIAIRE_DEFAULT,
            etatAvisEse: filtreValeur as string,
         };
      case "profil":
         return {
            ...FILTRE_BENEFICIAIRE_DEFAULT,
            profil: filtreValeur as string,
         };
      default:
         return FILTRE_BENEFICIAIRE_DEFAULT;
   }
}

export default function BeneficiaireTable() {
   const dispatch = useDispatch();
   const [searchParams] = useSearchParams();
   const navigate = useNavigate();
   const auth = useAuth();
   const { getPreferenceArray, preferencesChargees } = usePreferences();
   const [count, setCount] = React.useState<number>();
   const [hasImpersonate, setHasImpersonate] = useState(false);
   const [filtreBeneficiaire, setFiltreBeneficiaire] = useState<FiltreBeneficiaire>({
      ...filtreBeneficiaireDefault(
         searchParams.get("filtreType"),
         searchParams.get("filtreValeur"),
      ),
      // on applique le filtre favori des préférences de l'utilisateur s'il existe
      ...(searchParams.get("filtreType") === null
         ? {
              ...getPreferenceArray("filtresBeneficiaire")?.filter((f) => f.favori)[0]?.filtre,
              page: 1,
           }
         : null),
   });
   const { data: dataBeneficiaires, isFetching: isFetchingBeneficiaires } =
      useApi().useGetCollectionPaginated({
         path: "/beneficiaires",
         page: filtreBeneficiaire.page || 1,
         itemsPerPage: filtreBeneficiaire.itemsPerPage || 50,
         query: {
            ...filtreBeneficiaire,
         },
      });

   useEffect(() => {
      if (
         preferencesChargees &&
         getPreferenceArray("filtresBeneficiaire")?.filter((f) => f.favori).length > 0
      ) {
         setFiltreBeneficiaire({
            ...FILTRE_BENEFICIAIRE_DEFAULT,
            // on applique le filtre favori des préférences de l'utilisateur s'il existe
            ...{
               ...getPreferenceArray("filtresBeneficiaire")?.filter((f) => f.favori)[0]?.filtre,
               page: 1,
            },
         });
      }
      // eslint-disable-next-line react-hooks/exhaustive-deps
   }, [preferencesChargees]);

   useEffect(() => {
      if (searchParams.get("filtreType") && searchParams.get("filtreValeur")) {
         setFiltreBeneficiaire(
            filtreBeneficiaireDefault(
               searchParams.get("filtreType"),
               searchParams.get("filtreValeur"),
            ),
         );
      }
   }, [searchParams]);

   useEffect(() => {
      if (auth.impersonate && hasImpersonate) {
         queryClient.clear();
         dispatch(
            setAffichageFiltres(initialAffichageFiltres.affichage, initialAffichageFiltres.filtres),
         );
      }
   }, [hasImpersonate, auth.impersonate, dispatch]);

   useEffect(() => {
      setCount(dataBeneficiaires?.totalItems);
   }, [dataBeneficiaires]);

   const onClick = (record: IBeneficiaire) => {
      if (auth.user?.isGestionnaire) {
         navigate(`/beneficiaires/${record.uid}`);
      } else {
         dispatch(
            setDrawerUtilisateur({
               utilisateur: record["@id"] as string,
               role: RoleValues.ROLE_BENEFICIAIRE,
            }),
         );
      }
   };

   // Sticky header
   useEffect(() => {
      function handleScroll() {
         const table = document.querySelector("table") as HTMLElement;
         const tHead = document.querySelector(".ant-table-thead") as HTMLElement;
         tHead.style.top = `${document.documentElement.scrollTop - (table.getBoundingClientRect().top + window.scrollY - 80)}px`;
      }

      window.addEventListener("scroll", handleScroll);
      return () => {
         window.removeEventListener("scroll", handleScroll);
      };
   }, []);

   return (
      <>
         <BeneficiaireTableFilter
            filtreBeneficiaire={filtreBeneficiaire}
            setFiltreBeneficiaire={setFiltreBeneficiaire}
         />
         <Flex justify="space-between" align="center">
            <span className="legende">{getCountLibelle(count, "bénéficiaire")}</span>
            <div>
               {JSON.stringify(FILTRE_BENEFICIAIRE_DEFAULT) !==
                  JSON.stringify(filtreBeneficiaire) && (
                  <Button.Group>
                     <FiltreDescription
                        filtre={filtreBeneficiaire}
                        as="modal"
                        tooltip="Décrire le filtre en cours"
                     />
                     <Tooltip title="Retirer les filtres">
                        <Button
                           className="d-flex-inline-center mr-1"
                           icon={<Icon component={Unfilter} aria-label="Retirer les filtres" />}
                           onClick={() => setFiltreBeneficiaire(FILTRE_BENEFICIAIRE_DEFAULT)}
                        />
                     </Tooltip>
                  </Button.Group>
               )}
               {auth.user?.isGestionnaire && (
                  <>
                     <BeneficiaireTableExport filtreBeneficiaire={filtreBeneficiaire} />
                  </>
               )}
            </div>
         </Flex>
         <Table<IBeneficiaire>
            loading={isFetchingBeneficiaires}
            dataSource={dataBeneficiaires?.items || []}
            className="table-responsive table-thead-sticky mt-2"
            rowClassName={(_record, index) => (index % 2 === 1 ? "bg-grey-xlight" : "")}
            rowHoverable={false}
            pagination={{
               pageSize: filtreBeneficiaire.itemsPerPage || 50,
               total: dataBeneficiaires?.totalItems,
               current: filtreBeneficiaire.page || 1,
               showTotal: (total, range) => (
                  <div className="text-legende mr-1">
                     {range[0]} à {range[1]} / {total}
                  </div>
               ),
               showSizeChanger: true,
               pageSizeOptions: [25, 50, 100, 200],
            }}
            columns={beneficiaireTableColumns({
               user: auth.user,
               filter: filtreBeneficiaire,
               setFilter: setFiltreBeneficiaire,
               onBeneficiaireSelected: (beneficiaire) => {
                  onClick(beneficiaire);
               },
               onImpersonate: (uid) => {
                  navigate("/");
                  window.setTimeout(() => {
                     setHasImpersonate(true);
                     auth.setImpersonate(uid);
                  }, 500);
               },
            })}
            rowKey={(record) => record["@id"] as string}
            onChange={(
               pagination,
               _filters,
               sorter: SorterResult<IBeneficiaire> | SorterResult<IBeneficiaire>[],
            ) => {
               if (Array.isArray(sorter)) {
                  return;
               }
               setFiltreBeneficiaire({
                  ...filtreBeneficiaire,
                  page: pagination.current || filtreBeneficiaire.page || 1,
                  itemsPerPage: pagination.pageSize || filtreBeneficiaire.itemsPerPage || 50,
                  "order[nom]": ascendToAsc(sorter?.order),
               });
            }}
         />
      </>
   );
}
