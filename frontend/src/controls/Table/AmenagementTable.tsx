/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IAmenagement, ICategorieAmenagement, ITypeAmenagement } from "@api";
import { useAuth } from "@/auth/AuthProvider";
import { useNavigate } from "react-router";
import { useApi } from "@context/api/ApiProvider";
import { Table } from "antd";
import { amenagementTableColumns } from "@controls/Table/AmenagementTableColumns";
import { SorterResult } from "antd/es/table/interface";
import React, { useEffect, useRef, useState } from "react";
import { FiltreAmenagement, filtreAmenagementToApi } from "@controls/Table/AmenagementTableLayout";
import { ModalAmenagement } from "@controls/Modals/ModalAmenagement";
import { ModeAffichageAmenagement } from "@routes/gestionnaire/beneficiaires/Amenagements";

export function AmenagementTable(props: {
  filtreAmenagement: FiltreAmenagement;
  setFiltreAmenagement: React.Dispatch<React.SetStateAction<FiltreAmenagement>>;
  typesAmenagements?: ITypeAmenagement[];
  categoriesAmenagements?: ICategorieAmenagement[];
  setCount?: (count: number | undefined) => void;
}) {
  const auth = useAuth();
  const navigate = useNavigate();
  const [editedItem, setEditedItem] = useState<string>();
  const tableRef = useRef<HTMLDivElement>(null);

  const { data: amenagements, isFetching: isFetchingAmenagements } = useApi().useGetCollection({
    path: "/amenagements",
    query: filtreAmenagementToApi(props.filtreAmenagement, ModeAffichageAmenagement.ParAmenagement),
  });

  useEffect(() => {
    if (props.setCount) {
      props.setCount(amenagements?.totalItems);
    }
  }, [amenagements, props]);

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
      <div ref={tableRef}>
        <Table<IAmenagement>
          loading={isFetchingAmenagements}
          dataSource={amenagements?.items || []}
          rowClassName={(_record, index) => (index % 2 === 1 ? "bg-grey-light" : "")}
          rowHoverable={false}
          columns={amenagementTableColumns({
            filtre: props.filtreAmenagement,
            setFiltre: props.setFiltreAmenagement,
            typesAmenagements: props.typesAmenagements,
            categoriesAmenagements: props.categoriesAmenagements,
            navigate: navigate,
            isGestionnaire: auth?.user?.isGestionnaire,
            setEditedItem: auth?.user?.isGestionnaire ? undefined : setEditedItem,
          })}
          className="table-responsive table-thead-sticky mt-2"
          pagination={{
            pageSize: props.filtreAmenagement.itemsPerPage,
            total: amenagements?.totalItems,
            current: props.filtreAmenagement.page,
            showTotal: (total, range) => (
              <div className="text-legende mr-1">
                {range[0]} à {range[1]} / {total}
              </div>
            ),
            showSizeChanger: true,
            pageSizeOptions: [25, 50, 100, 200],
          }}
          rowKey={(record) => record["@id"] as string}
          onChange={(
            pagination,
            _filters,
            sorter: SorterResult<IAmenagement> | SorterResult<IAmenagement>[],
          ) => {
            if (Array.isArray(sorter)) {
              return;
            }
            props.setFiltreAmenagement({
              ...props.filtreAmenagement,
              page: pagination.current ?? 1,
              itemsPerPage: pagination.pageSize ?? 25,
            });
          }}
        />
      </div>
      {editedItem !== undefined && (
        <ModalAmenagement
          amenagementId={editedItem}
          open={true}
          setOpen={(open) => {
            if (!open) setEditedItem(undefined);
          }}
        />
      )}
    </>
  );
}
