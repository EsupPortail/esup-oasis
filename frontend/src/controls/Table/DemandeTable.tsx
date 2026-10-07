/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { IDemande, PREFETCH_ETAT_DEMANDE } from "@api";
import { Button, Flex, Space, Table } from "antd";
import { useApi } from "@context/api/ApiProvider";
import { useAuth } from "@/auth/AuthProvider";
import { SorterResult } from "antd/es/table/interface";
import { useNavigate, useSearchParams } from "react-router";
import DemandeTableExport from "@controls/Table/DemandeTableExport";
import { demandeTableColumns } from "@controls/Table/DemandeTableColumns";
import { DemandeTableFilters } from "@controls/Table/DemandeTableFilters";
import Unfilter from "@/assets/images/unfilter.svg?react";
import Icon from "@ant-design/icons";
import { ascendToAsc } from "@utils/array";
import { RefsTourDemandes } from "@routes/gestionnaire/demandeurs/Demandeurs";
import FiltreDescription from "@controls/Table/FiltreDescription";
import { usePreferences } from "@context/utilisateurPreferences/UtilisateurPreferencesProvider";
import { useFiltreSessionStorage } from "@controls/Table/hooks/useFiltreSessionStorage";
import { FiltreSessionSwitch } from "@controls/Table/FiltreSessionSwitch";
import { getCountLibelle } from "@utils/table";

export const FILTRE_DEMANDE_DEFAULT: FiltreDemande = {
  "order[demandeur.nom]": "asc",
  archivees: false,
  itemsPerPage: 25,
  page: 1,
};

export type FiltreDemande = {
  /** @description The collection page number */
  page: number;
  /** @description The number of items per page */
  itemsPerPage: number;
  "demandeur.nom"?: string;
  "demandeur.prenom"?: string;
  etat?: string;
  "etat[]"?: string[];
  "campagne.typeDemande"?: string;
  "campagne.typeDemande[]"?: string[];
  "campagne.typeDemande.libelle"?: string;
  demandeur?: string;
  "demandeur[]"?: string[];
  "order[demandeur.nom]"?: "asc" | "desc";
  "order[dateDepot]"?: "asc" | "desc";
  "composante[]"?: string[];
  "formation[]"?: string[];
  "discipline[]"?: string[];
  "gestionnaire[]"?: string[];
  archivees?: boolean;
};

function filtreDemandeDefault(
  filtreType: string | null,
  filtreValeur: string | null,
): FiltreDemande {
  switch (filtreType) {
    case "etat":
      return {
        ...FILTRE_DEMANDE_DEFAULT,
        "etat[]": [filtreValeur as string],
      };
    default:
      return FILTRE_DEMANDE_DEFAULT;
  }
}

const SESSION_KEY_FILTRE_DEMANDE = "oasis:filter:demande";

export default function DemandeTable(props: { refs: RefsTourDemandes; affichageTour?: boolean }) {
  const auth = useAuth();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { getPreferenceArray, preferencesChargees } = usePreferences();
  const { enabled: sessionEnabled, toggle: toggleSession } = useFiltreSessionStorage();

  // Capture si sessionStorage avait des données au montage (indépendamment de sessionEnabled,
  // car sessionEnabled peut être faux avant que les préférences ne chargent)
  const hadSessionFilter = useRef(
    searchParams.get("filtreType") === null && !!sessionStorage.getItem(SESSION_KEY_FILTRE_DEMANDE),
  );

  const [filtreDemande, setFiltreDemande] = useState<FiltreDemande>(() => {
    // Priorité 1 : filtre URL
    if (searchParams.get("filtreType") !== null) {
      return filtreDemandeDefault(searchParams.get("filtreType"), searchParams.get("filtreValeur"));
    }
    // Priorité 2 : filtre de session (navigation intra-session, seulement si activé)
    if (sessionEnabled) {
      try {
        const stored = sessionStorage.getItem(SESSION_KEY_FILTRE_DEMANDE);
        if (stored) return JSON.parse(stored) as FiltreDemande;
      } catch {
        /* ignore */
      }
    }
    // Priorité 3 : filtre favori des préférences
    return {
      ...FILTRE_DEMANDE_DEFAULT,
      ...{
        ...getPreferenceArray("filtresDemande")?.filter((f) => f.favori)[0]?.filtre,
        page: 1,
      },
    };
  });

  // Persiste le filtre en session à chaque changement (seulement si activé)
  useEffect(() => {
    if (sessionEnabled) {
      sessionStorage.setItem(SESSION_KEY_FILTRE_DEMANDE, JSON.stringify(filtreDemande));
    }
  }, [filtreDemande, sessionEnabled]);

  const { data: dataDemandes, isFetching: isFetchingDemandes } = useApi().useGetCollectionPaginated(
    {
      path: "/demandes",
      page: filtreDemande.page || 1,
      itemsPerPage: filtreDemande.itemsPerPage,
      query: {
        ...filtreDemande,
        format_simple: true,
      },
    },
  );
  const { data: etats } = useApi().useGetFullCollection(PREFETCH_ETAT_DEMANDE);

  // Synchronisation une fois les préférences chargées : point de vérité pour session + favori
  useEffect(() => {
    if (!preferencesChargees) return;

    // sessionEnabled est maintenant fiable : si activé et sessionStorage avait des données, les appliquer
    if (sessionEnabled && hadSessionFilter.current && searchParams.get("filtreType") === null) {
      try {
        const stored = sessionStorage.getItem(SESSION_KEY_FILTRE_DEMANDE);
        if (stored) {
          // eslint-disable-next-line react-hooks/set-state-in-effect
          setFiltreDemande(JSON.parse(stored) as FiltreDemande);
          return;
        }
      } catch {
        /* ignore */
      }
    }

    // Session désactivée ou vide : appliquer le favori s'il existe
    const favorites = getPreferenceArray("filtresDemande")?.filter((f) => f.favori);
    if (favorites?.length > 0) {
      setFiltreDemande({
        ...FILTRE_DEMANDE_DEFAULT,
        ...{ ...favorites[0].filtre, page: 1 },
      });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [preferencesChargees]);

  useEffect(() => {
    if (searchParams.get("filtreType") && searchParams.get("filtreValeur")) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setFiltreDemande(
        filtreDemandeDefault(searchParams.get("filtreType"), searchParams.get("filtreValeur")),
      );
    }
  }, [searchParams]);

  const count = dataDemandes?.totalItems;

  const hasActiveFilters = useMemo(
    () => JSON.stringify(filtreDemande) !== JSON.stringify(FILTRE_DEMANDE_DEFAULT),
    [filtreDemande],
  );

  const handleImpersonate = useCallback(
    (uid: string) => {
      navigate(`/impersonate/${uid}`);
    },
    [navigate],
  );

  const handleDemandeSelected = useCallback(
    (demande: IDemande) => {
      if (demande.id == null) return;
      navigate(`/demandes/${demande.id}`);
    },
    [navigate],
  );

  const handleTableChange = useCallback(
    (
      pagination: Parameters<NonNullable<React.ComponentProps<typeof Table>["onChange"]>>[0],
      _filters: Parameters<NonNullable<React.ComponentProps<typeof Table>["onChange"]>>[1],
      sorter: SorterResult<IDemande> | SorterResult<IDemande>[],
    ) => {
      if (Array.isArray(sorter)) {
        setFiltreDemande((prev) => ({
          ...prev,
          page: pagination.current ?? prev.page,
          itemsPerPage: pagination.pageSize ?? prev.itemsPerPage,
        }));
      } else if (sorter.field === "demandeur.nom") {
        setFiltreDemande((prev) => ({
          ...prev,
          "order[demandeur.nom]": ascendToAsc(sorter.order),
          "order[dateDepot]": undefined,
          page: pagination.current ?? prev.page,
          itemsPerPage: pagination.pageSize ?? prev.itemsPerPage,
        }));
      } else if (sorter.field === "dateDepot") {
        setFiltreDemande((prev) => ({
          ...prev,
          "order[dateDepot]": ascendToAsc(sorter.order),
          "order[demandeur.nom]": undefined,
          page: pagination.current ?? prev.page,
          itemsPerPage: pagination.pageSize ?? prev.itemsPerPage,
        }));
      }
    },
    [],
  );

  // Sticky header
  useEffect(() => {
    let rafId: number;
    function handleScroll() {
      cancelAnimationFrame(rafId);
      rafId = requestAnimationFrame(() => {
        const container = props.refs.table.current;
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
  }, [props.refs.table]);

  return (
    <>
      <DemandeTableFilters
        filtreDemande={filtreDemande}
        setFiltreDemande={setFiltreDemande}
        defaultFilter={FILTRE_DEMANDE_DEFAULT}
        refs={props.refs}
        affichageTour={props.affichageTour}
      />
      <Flex justify="space-between" align="center">
        <span className="text-legende">{getCountLibelle(count, "demande")}</span>
        <Space size="large">
          <FiltreSessionSwitch
            id="conserver-filtres-demande"
            enabled={sessionEnabled}
            toggle={toggleSession}
          />
          {hasActiveFilters && (
            <Space.Compact>
              <FiltreDescription
                filtre={filtreDemande}
                as="modal"
                tooltip="Décrire le filtre en cours"
              />
              <Button
                className="d-flex-inline-center mr-1"
                icon={<Icon component={Unfilter} aria-hidden />}
                onClick={() => setFiltreDemande(FILTRE_DEMANDE_DEFAULT)}
              >
                Retirer les filtres
              </Button>
            </Space.Compact>
          )}
          <DemandeTableExport filtreDemande={filtreDemande} />
        </Space>
      </Flex>
      <div ref={props.refs.table} aria-live="polite" aria-busy={isFetchingDemandes}>
        <Table<IDemande>
          loading={isFetchingDemandes}
          aria-label="Liste des demandes d'accompagnement"
          dataSource={dataDemandes?.items || []}
          className="table-responsive table-thead-sticky mt-2"
          rowClassName={(_record, index) => (index % 2 === 1 ? "bg-grey-light" : "")}
          rowHoverable={false}
          style={props.affichageTour ? { maxHeight: 400, overflowY: "auto" } : undefined}
          pagination={{
            pageSize: filtreDemande.itemsPerPage,
            total: dataDemandes?.totalItems,
            current: filtreDemande.page,
            showTotal: (total, range) => (
              <div className="text-legende mr-1">
                {range[0]} à {range[1]} / {total}
              </div>
            ),
            showSizeChanger: true,
            pageSizeOptions: [25, 50, 100, 200],
          }}
          rowKey={(record) => record["@id"] as string}
          columns={demandeTableColumns({
            filter: filtreDemande,
            setFilter: setFiltreDemande,
            user: auth.user,
            etats: etats?.items,
            onImpersonate: handleImpersonate,
            onDemandeSelected: handleDemandeSelected,
          })}
          onChange={handleTableChange}
        />
      </div>
    </>
  );
}
