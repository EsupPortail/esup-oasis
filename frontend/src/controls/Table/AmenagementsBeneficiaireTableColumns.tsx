/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

/* eslint-disable @typescript-eslint/no-explicit-any */

import { DomaineAmenagementInfos, RoleValues, Utilisateur } from "@lib";
import React from "react";
import { Badge, Button, Flex, Space, Tooltip } from "antd";
import Icon, { CheckOutlined, MinusOutlined, PauseOutlined } from "@ant-design/icons";
import { ComposanteItem } from "@controls/Items/ComposanteItem";
import { EtudiantItem } from "@controls/Items/EtudiantItem";
import {
  AmenagementCellData,
  IAmenagementsBeneficiaireTableDataSource,
  TypesDomainesAmenagements,
} from "@controls/Table/AmenagementsBeneficiaireTable";
import BooleanState from "@controls/State/BooleanState";
import { ListeUtilisateurTag } from "@controls/Tags/ListeUtilisateurTag";
import { BeneficiaireAvisEseAvatar } from "@controls/Avatars/BeneficiaireAvisEseAvatar";
import { NavigateFunction } from "react-router";
import ExternalLink from "@/assets/images/external-link.svg?react";
import { FiltreAmenagement } from "@controls/Table/AmenagementTableLayout";
import { EllipsisMiddle } from "@controls/Typography/EllipsisMiddle";
import { EllipsisParagraph } from "@controls/Typography/EllipsisParagraph";
import { ascToAscend } from "@utils/array";
import { FilterProps } from "@utils/table";
import { ColumnsType } from "antd/lib/table";
import { IBeneficiaire, ICategorieAmenagement, ITypeAmenagement } from "@api";
import { ColumnType } from "antd/es/table";
import { FiltreBeneficiaire } from "@controls/Table/BeneficiaireTable";
import { env } from "@/env";

function AmenagementBeneficiaireTableCell(props: {
  amenagement: AmenagementCellData | null | undefined;
  couleur: string;
  label: string;
}) {
  return props.amenagement ? (
    <>
      <BooleanState
        value={true}
        onLabel="Oui"
        colorOn={props.couleur}
        className={props.couleur === "yellow" ? "text-yellow-dark border-yellow" : ""}
        showTooltip
        iconOn={<CheckOutlined />}
        tooltip={props.label}
      />
      {props.amenagement?.commentaire && (
        <EllipsisParagraph
          content={props.amenagement.commentaire}
          className="text-legende fs-08 mt-1 mb-0"
        />
      )}
    </>
  ) : (
    ""
  );
}

type AmenagementBeneficiaireHierarchie = {
  key: string;
  domaine: DomaineAmenagementInfos;
  categories: {
    key: string;
    categorie: ICategorieAmenagement;
    types: ITypeAmenagement[];
  }[];
};

export function amenagementsBeneficiaireTableColumns(props: {
  filtre: FiltreAmenagement;
  setFiltre: React.Dispatch<React.SetStateAction<FiltreAmenagement>>;
  typesAmenagements: TypesDomainesAmenagements[];
  categoriesAmenagements: ICategorieAmenagement[];
  navigate: NavigateFunction;
  user: Utilisateur;
  fixPremiereColonne: boolean;
}): ColumnsType<IAmenagementsBeneficiaireTableDataSource> {
  function buildHierarchie(): AmenagementBeneficiaireHierarchie[] {
    const hierarchie: Record<string, any> = [];
    props.typesAmenagements.forEach((ta) => {
      if (ta.typeAmenagement === undefined || !ta.domaine) return;
      if (hierarchie[ta.domaine.id] === undefined) {
        hierarchie[ta.domaine.id] = {
          key: ta.domaine.id,
          domaine: ta.domaine,
          categories: [],
        };
      }

      const cat = props.categoriesAmenagements.find(
        (c) => c["@id"] === ta.typeAmenagement.categorie,
      );
      if (cat && cat["@id"]) {
        if (hierarchie[ta.domaine.id].categories[cat["@id"]] === undefined) {
          hierarchie[ta.domaine.id].categories[cat["@id"]] = {
            key: cat["@id"],
            categorie: cat,
            types: [],
          };
        }
        hierarchie[ta.domaine.id].categories[cat["@id"]].types.push(ta.typeAmenagement);
      }
    });

    return Object.values(hierarchie).sort((a, b) => a.domaine.order - b.domaine.order);
  }

  return [
    {
      title: "Bénéficiaire",
      fixed: props.fixPremiereColonne ? "left" : undefined,
      dataIndex: "key",
      sortDirections: ["ascend", "descend"],
      sorter: true,
      defaultSortOrder: "ascend",
      sortOrder: ascToAscend(props.filtre?.["order[nom]"]),
      ...FilterProps<FiltreBeneficiaire>("nom", props.filtre, props.setFiltre),
      filteredValue: props.filtre?.nom ? [props.filtre?.nom] : null,
      onCell: props.user.isGestionnaire
        ? (record: any) => {
            return {
              className: "pointer",
              onClick: () => {
                props.navigate(`/beneficiaires/${record.uid as string}`);
              },
            };
          }
        : undefined,
      render: (_value: string, record: any) => {
        return (
          <Flex justify="space-between" align="center">
            <EtudiantItem
              utilisateurId={record.key}
              responsive="lg"
              role={RoleValues.ROLE_BENEFICIAIRE}
              highlight={props.filtre.nom}
            />
            {props.user.isGestionnaire && (
              <Tooltip title="Ouvrir dans un nouvel onglet">
                <Button
                  size="small"
                  type="text"
                  className="text-light"
                  icon={<Icon component={ExternalLink} className="fs-08" />}
                  onClick={(e) => {
                    e.stopPropagation();
                    window.open(`/beneficiaires/${record.uid as string}`, "_blank");
                  }}
                />
              </Tooltip>
            )}
          </Flex>
        );
      },
    } as ColumnType<IBeneficiaire>,
    {
      title: "Inscription",
      dataIndex: "inscription",
      render: (value: any) => {
        return value ? (
          <Space orientation="vertical" size={2}>
            <ComposanteItem composanteId={value?.formation?.composante["@id"]} />
            <EllipsisMiddle
              className="light"
              style={{ maxWidth: 350 }}
              suffixCount={12}
              content={value?.formation?.libelle as string}
              expandable
            />
          </Space>
        ) : (
          <MinusOutlined />
        );
      },
    },
    props.user.isGestionnaire
      ? {
          title: <div style={{ width: 100 }}>Avis {env.REACT_APP_ESPACE_SANTE_ABV || "santé"}</div>,
          dataIndex: "avisESE",
          className: "text-center",
          render: (_value: string, record: any) => {
            return (
              <BeneficiaireAvisEseAvatar
                utilisateurId={record.key}
                className="fs-12"
                showLabel={false}
                direction="vertical"
              />
            );
          },
        }
      : null,

    ...buildHierarchie()
      .filter((d) => !props.filtre.restreindreColonnes || props.filtre.domaine === d.domaine.id)
      .map((d: AmenagementBeneficiaireHierarchie) => {
        return {
          title: d.domaine.libelleLongSingulier,
          key: d.domaine.id,
          className: `text-center bg-${d.domaine.couleur}-light`,
          children: [
            {
              title: (
                <Tooltip title="Nombre d'aménagements du domaine pour le bénéficiaire">
                  <PauseOutlined aria-hidden rotate={90} />
                </Tooltip>
              ),
              key: `${d.domaine.id}_count`,
              className: `text-center bg-${d.domaine.couleur}-xlight`,
              render: (_value: any, record: any) => {
                const nb = props.typesAmenagements.filter(
                  (c) =>
                    c.domaine.id === d.domaine.id && record[c.typeAmenagement["@id"] as string],
                ).length;
                return nb > 0 && <Badge color={d.domaine.couleur} count={nb} />;
              },
            },
            ...Object.values(d.categories).map((c, index) => {
              return {
                key: c.categorie["@id"],
                title: c.categorie.libelle,
                className: `text-center fs-09 ${index % 2 === 1 ? `bg-${d.domaine.couleur}-xlight` : `bg-${d.domaine.couleur}-xxlight`}`,
                children: [
                  ...c.types.map((t) => ({
                    title: <div style={{ minWidth: 125 }}>{t.libelle}</div>,
                    dataIndex: t["@id"],
                    key: t["@id"],
                    className: `text-center fs-09 ${index % 2 === 1 ? `bg-${d.domaine.couleur}-xlight` : `bg-${d.domaine.couleur}-xxlight`}`,
                    render: (value: AmenagementCellData) => (
                      <AmenagementBeneficiaireTableCell
                        amenagement={value}
                        couleur={d.domaine.couleur}
                        label={`${c.categorie.libelle} > ${t.libelle}`}
                      />
                    ),
                  })),
                ],
              };
            }),
          ],
        };
      }),

    props.user.isGestionnaire
      ? {
          title: <div style={{ width: 200 }}>Tags</div>,
          dataIndex: "tags",
          render: (_value: string, record: any) => {
            return <ListeUtilisateurTag utilisateurId={record.key} />;
          },
        }
      : null,
  ].filter((c) => c !== null) as ColumnsType<IAmenagementsBeneficiaireTableDataSource>;
}
