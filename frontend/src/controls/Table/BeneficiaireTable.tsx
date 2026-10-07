/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useEffect, useRef, useState } from "react";
import { IBeneficiaire } from "@api";
import { beneficiaireTableColumns } from "@controls/Table/BeneficiaireTableColumns";
import { RoleValues } from "@lib";
import { Button, Flex, Space, Table } from "antd";
import Icon from "@ant-design/icons";
import { useApi } from "@context/api/ApiProvider";
import { useAuth } from "@/auth/AuthProvider";
import { useDrawers } from "@context/drawers/DrawersContext";
import BeneficiaireTableExport from "@controls/Table/BeneficiaireTableExport";
import { SorterResult } from "antd/es/table/interface";
import { useNavigate, useSearchParams } from "react-router";
import Unfilter from "@/assets/images/unfilter.svg?react";
import { BeneficiaireTableFilter } from "@controls/Table/BeneficiaireTableFilter";
import { ascendToAsc } from "@utils/array";
import FiltreDescription from "@controls/Table/FiltreDescription";
import { usePreferences } from "@context/utilisateurPreferences/UtilisateurPreferencesProvider";
import { useFiltreSessionStorage } from "@controls/Table/hooks/useFiltreSessionStorage";
import { FiltreSessionSwitch } from "@controls/Table/FiltreSessionSwitch";
import { getCountLibelle } from "@utils/table";
import dayjs from "dayjs";

export const FILTRE_BENEFICIAIRE_DEFAULT: FiltreBeneficiaire = {
  "order[nom]": "asc" as "asc" | "desc" | undefined,
  "beneficiaires.avecAccompagnement": true,
  "filtreBeneficiaire[date]": dayjs().format("YYYY-MM-DD"),
  page: 1,
  itemsPerPage: 25,
};

export interface FiltreBeneficiaire {
  /** @description profil pour lequel on veut les bénéficiaires valides */
  "filtreBeneficiaire[profil]"?: string;
  /** @description date pour laquelle on veut les bénéficiaires valides */
  "filtreBeneficiaire[date]"?: string;
  /** @description date avant laquelle on veut les bénéficiaires valides */
  "filtreBeneficiaire[avant]"?: string;
  /** @description date après laquelle on veut les bénéficiaires valides */
  "filtreBeneficiaire[apres]"?: string;
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
        "filtreBeneficiaire[profil]": filtreValeur as string,
      };
    default:
      return FILTRE_BENEFICIAIRE_DEFAULT;
  }
}

const SESSION_KEY_FILTRE_BENEFICIAIRE = "oasis:filter:beneficiaire";

export default function BeneficiaireTable() {
  const { setDrawerUtilisateur } = useDrawers();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const auth = useAuth();
  const { getPreferenceArray, preferencesChargees } = usePreferences();
  const { enabled: sessionEnabled, toggle: toggleSession } = useFiltreSessionStorage();

  // Capture si sessionStorage avait des données au montage (indépendamment de sessionEnabled,
  // car sessionEnabled peut être faux avant que les préférences ne chargent)
  const hadSessionFilter = useRef(
    searchParams.get("filtreType") === null &&
      !!sessionStorage.getItem(SESSION_KEY_FILTRE_BENEFICIAIRE),
  );
  const tableRef = useRef<HTMLDivElement>(null);

  const [filtreBeneficiaire, setFiltreBeneficiaire] = useState<FiltreBeneficiaire>(() => {
    // Priorité 1 : filtre URL
    if (searchParams.get("filtreType") !== null) {
      return filtreBeneficiaireDefault(
        searchParams.get("filtreType"),
        searchParams.get("filtreValeur"),
      );
    }
    // Priorité 2 : filtre de session (seulement si activé)
    if (sessionEnabled) {
      try {
        const stored = sessionStorage.getItem(SESSION_KEY_FILTRE_BENEFICIAIRE);
        if (stored) return JSON.parse(stored) as FiltreBeneficiaire;
      } catch {
        /* ignore */
      }
    }
    // Priorité 3 : filtre favori des préférences
    return {
      ...FILTRE_BENEFICIAIRE_DEFAULT,
      ...{
        ...getPreferenceArray("filtresBeneficiaire")?.filter((f) => f.favori)[0]?.filtre,
        page: 1,
      },
    };
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

  // Persiste le filtre en session à chaque changement (seulement si activé)
  useEffect(() => {
    if (sessionEnabled) {
      sessionStorage.setItem(SESSION_KEY_FILTRE_BENEFICIAIRE, JSON.stringify(filtreBeneficiaire));
    }
  }, [filtreBeneficiaire, sessionEnabled]);

  // Synchronisation une fois les préférences chargées : point de vérité pour session + favori
  useEffect(() => {
    if (!preferencesChargees) return;

    if (sessionEnabled && hadSessionFilter.current && searchParams.get("filtreType") === null) {
      try {
        const stored = sessionStorage.getItem(SESSION_KEY_FILTRE_BENEFICIAIRE);
        if (stored) {
          // eslint-disable-next-line react-hooks/set-state-in-effect
          setFiltreBeneficiaire(JSON.parse(stored) as FiltreBeneficiaire);
          return;
        }
      } catch {
        /* ignore */
      }
    }

    const favorites = getPreferenceArray("filtresBeneficiaire")?.filter((f) => f.favori);
    if (favorites?.length > 0) {
      setFiltreBeneficiaire({
        ...FILTRE_BENEFICIAIRE_DEFAULT,
        ...{ ...favorites[0].filtre, page: 1 },
      });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [preferencesChargees]);

  useEffect(() => {
    if (searchParams.get("filtreType") && searchParams.get("filtreValeur")) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setFiltreBeneficiaire(
        filtreBeneficiaireDefault(searchParams.get("filtreType"), searchParams.get("filtreValeur")),
      );
    }
  }, [searchParams]);

  const count = dataBeneficiaires?.totalItems;

  const onClick = (record: IBeneficiaire) => {
    if (auth.user?.isGestionnaire) {
      navigate(`/beneficiaires/${record.uid}`);
    } else {
      setDrawerUtilisateur({
        utilisateur: record["@id"] as string,
        role: RoleValues.ROLE_BENEFICIAIRE,
      });
    }
  };

  // Sticky header
  useEffect(() => {
    let rafId: number;
    function handleScroll() {
      cancelAnimationFrame(rafId);
      rafId = requestAnimationFrame(() => {
        const container = tableRef.current;
        const table = container?.querySelector("table");
        const tHead = container?.querySelector<HTMLElement>(".ant-table-thead");
        if (!table || !tHead) return;
        tHead.style.top = `${document.documentElement.scrollTop - (table.getBoundingClientRect().top + window.scrollY - 80)}px`;
      });
    }

    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => {
      cancelAnimationFrame(rafId);
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
        <span className="text-legende">{getCountLibelle(count, "bénéficiaire")}</span>
        <Space size="large">
          <FiltreSessionSwitch
            id="conserver-filtres-beneficiaire"
            enabled={sessionEnabled}
            toggle={toggleSession}
          />
          <div>
            {JSON.stringify(FILTRE_BENEFICIAIRE_DEFAULT) !== JSON.stringify(filtreBeneficiaire) && (
              <Space.Compact>
                <FiltreDescription
                  filtre={filtreBeneficiaire}
                  as="modal"
                  tooltip="Décrire le filtre en cours"
                />
                <Button
                  className="d-flex-inline-center mr-1"
                  icon={<Icon component={Unfilter} aria-label="Retirer les filtres" />}
                  onClick={() => setFiltreBeneficiaire(FILTRE_BENEFICIAIRE_DEFAULT)}
                >
                  Retirer les filtres
                </Button>
              </Space.Compact>
            )}
            {auth.user?.isGestionnaire && (
              <>
                <BeneficiaireTableExport filtreBeneficiaire={filtreBeneficiaire} />
              </>
            )}
          </div>
        </Space>
      </Flex>
      <div ref={tableRef}>
        <Table<IBeneficiaire>
          loading={isFetchingBeneficiaires}
          dataSource={dataBeneficiaires?.items || []}
          className="table-responsive table-thead-sticky mt-2"
          rowClassName={(_record, index) => (index % 2 === 1 ? "bg-grey-light" : "")}
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
              navigate(`/impersonate/${uid}`);
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
      </div>
    </>
  );
}
