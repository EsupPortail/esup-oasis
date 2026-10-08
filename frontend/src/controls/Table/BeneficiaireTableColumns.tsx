/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { ColumnType } from "antd/es/table";
import { IBeneficiaire, IUtilisateur } from "@api";
import { BeneficiaireProfilItem } from "@controls/Items/BeneficiaireProfilItem";
import { ChargesAccompagnementsItem } from "@controls/Items/ChargesAccompagnementsItem";
import { ComposanteItem } from "@controls/Items/ComposanteItem";
import React from "react";
import { Button, Popconfirm, Space, Tooltip } from "antd";
import Icon, { EyeOutlined, MinusOutlined, UserSwitchOutlined } from "@ant-design/icons";
import { ascToAscend } from "@utils/array";
import { FilterProps } from "@utils/table";
import { FiltreBeneficiaire } from "@controls/Table/BeneficiaireTable";
import { RoleValues, Utilisateur } from "@lib";
import ExternalLink from "@/assets/images/external-link.svg?react";
import { UtilisateurTag } from "@controls/Tags/UtilisateurTag";
import {
  BeneficiaireAvisEseAvatar,
  EtatAvisEse,
} from "@controls/Avatars/BeneficiaireAvisEseAvatar";
import UtilisateurAvatarImage from "@controls/Avatars/UtilisateurAvatarImage";
import { EllipsisMiddle } from "@controls/Typography/EllipsisMiddle";
import Highlighter from "react-highlight-words";
import { removeAccents } from "@utils/string";
import { DecisionEtablissementAvatar } from "@controls/Avatars/DecisionEtablissementAvatar";
import { UseStateDispatch } from "@utils/utils";
import { env } from "@/env";
import { inscriptionsActives } from "@lib/Inscriptions";
import { CopyableTextCell } from "@controls/Typography/CopyableTextCell";

export interface TableColumnOption {
  key: string;
  label: string;
}

export const BENEFICIAIRE_TABLE_COLUMNS_KEYS = {
  NOM: "nom",
  COMPOSANTE: "composantes",
  INSCRIPTION: "inscription",
  TAGS: "tags",
  PROFILS: "profils",
  ETAT_AVIS_ESE: "etatAvisEse",
  DECISION_ETAB: "IDecisionEtablissement",
  GESTIONNAIRE: "gestionnaire[]",
  EMAIL: "email",
  NUM_ETUDIANT: "numeroEtudiant",
  STATUT: "statut",
  ACTIONS: "actions",
} as const;

export function getBeneficiaireTableColumnOptions(
  isGestionnaire?: boolean,
  colonnesComplementaires?: string[],
): TableColumnOption[] {
  const options: TableColumnOption[] = [
    { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.NOM, label: "Bénéficiaire" },
    { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.COMPOSANTE, label: "Composantes" },
    { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.INSCRIPTION, label: "Inscription" },
    { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.TAGS, label: "Tags" },
    ...(isGestionnaire
      ? [
          { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.PROFILS, label: "Profils actifs" },
          {
            key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.ETAT_AVIS_ESE,
            label: `Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"}`,
          },
        ]
      : []),
    { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.DECISION_ETAB, label: "Décision étab." },
    { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.GESTIONNAIRE, label: "Chargé•es d'acc." },
    { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.EMAIL, label: "Email" },
    { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.NUM_ETUDIANT, label: "Numéro étudiant" },
    { key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.STATUT, label: "Statut" },
  ];

  if (colonnesComplementaires) {
    const existingKeys = new Set(options.map((o) => o.key));
    colonnesComplementaires.forEach((cle) => {
      if (cle && !existingKeys.has(cle)) {
        options.push({ key: cle, label: cle });
        existingKeys.add(cle);
      }
    });
  }

  options.push({ key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.ACTIONS, label: "Actions" });
  return options;
}

export function getBeneficiaireTableInitialColumns(isGestionnaire?: boolean): string[] {
  return [
    BENEFICIAIRE_TABLE_COLUMNS_KEYS.NOM,
    BENEFICIAIRE_TABLE_COLUMNS_KEYS.INSCRIPTION,
    BENEFICIAIRE_TABLE_COLUMNS_KEYS.TAGS,
    ...(isGestionnaire
      ? [BENEFICIAIRE_TABLE_COLUMNS_KEYS.PROFILS, BENEFICIAIRE_TABLE_COLUMNS_KEYS.ETAT_AVIS_ESE]
      : []),
    BENEFICIAIRE_TABLE_COLUMNS_KEYS.DECISION_ETAB,
    BENEFICIAIRE_TABLE_COLUMNS_KEYS.GESTIONNAIRE,
    BENEFICIAIRE_TABLE_COLUMNS_KEYS.ACTIONS,
  ];
}

// --- Composants de cellules réutilisables ---

function NomCell({ record, searchedNom }: { record: IBeneficiaire; searchedNom?: string }) {
  return (
    <Space>
      <UtilisateurAvatarImage
        as="img"
        utilisateur={record as IUtilisateur}
        width={48}
        size={48}
        role={RoleValues.ROLE_BENEFICIAIRE}
        className="border-0"
        responsive="lg"
      />
      <span>
        <span className="semi-bold">
          <Highlighter
            textToHighlight={(record?.nom || "").toLocaleUpperCase()}
            searchWords={[removeAccents(searchedNom || "")]}
          />
        </span>{" "}
        <span className="light">{record?.prenom}</span>
      </span>
    </Space>
  );
}

function ComposantesCell({ record }: { record: IBeneficiaire }) {
  const actives = inscriptionsActives(record.inscriptions);
  if (actives.length === 0) return <MinusOutlined />;

  return (
    <Space className="mt-05 mb-05" orientation="vertical" size={2}>
      {actives.map((inscription) => (
        <ComposanteItem
          key={inscription["@id"]}
          composanteId={inscription?.formation?.composante}
        />
      ))}
    </Space>
  );
}

function InscriptionCell({ record }: { record: IBeneficiaire }) {
  const actives = inscriptionsActives(record.inscriptions);
  if (actives.length === 0) return <MinusOutlined />;

  return (
    <>
      {actives.map((inscription) => (
        <Space key={inscription["@id"]} className="mt-05 mb-05" orientation="vertical" size={2}>
          <ComposanteItem composanteId={inscription?.formation?.composante} />
          <EllipsisMiddle
            className="light"
            style={{ maxWidth: 350 }}
            suffixCount={12}
            content={inscription?.formation?.libelle as string}
            expandable
          />
        </Space>
      ))}
    </>
  );
}

function ActionsCell({
  record,
  canImpersonate,
  isGestionnaire,
  onBeneficiaireSelected,
  onImpersonate,
}: {
  record: IBeneficiaire;
  canImpersonate: boolean;
  isGestionnaire?: boolean;
  onBeneficiaireSelected: (beneficiaire: IBeneficiaire) => void;
  onImpersonate: (uid: string) => void;
}) {
  return (
    <Space>
      {canImpersonate && (
        <Popconfirm
          title="Êtes-vous sûr de vouloir prendre l'identité de cet utilisateur ?"
          onConfirm={() => onImpersonate(record.uid as string)}
          okText="Oui"
          cancelText="Non"
          placement="left"
        >
          <Tooltip title="Prendre l'identité">
            <Button icon={<UserSwitchOutlined />} onClick={(ev) => ev.stopPropagation()} />
          </Tooltip>
        </Popconfirm>
      )}
      <Space.Compact>
        <Button icon={<EyeOutlined />} onClick={() => onBeneficiaireSelected(record)}>
          Voir
        </Button>
        {isGestionnaire && (
          <Tooltip title="Ouvrir dans un nouvel onglet">
            <Button
              className="text-light"
              icon={<Icon component={ExternalLink} className="fs-08" />}
              onClick={() => window.open(`/beneficiaires/${record.uid}`, "_blank")}
            />
          </Tooltip>
        )}
      </Space.Compact>
    </Space>
  );
}

// --- Définition des colonnes ---

interface TableBeneficiairesColumnsProps {
  user: Utilisateur | undefined;
  filter: FiltreBeneficiaire;
  setFilter: UseStateDispatch<FiltreBeneficiaire>;
  onBeneficiaireSelected: (beneficiaire: IBeneficiaire) => void;
  onImpersonate: (uid: string) => void;
  colonnesVisibles?: string[];
  colonnesComplementaires?: string[];
}

export function beneficiaireTableColumns({
  user,
  filter,
  setFilter,
  onBeneficiaireSelected,
  onImpersonate,
  colonnesVisibles,
  colonnesComplementaires,
}: TableBeneficiairesColumnsProps): ColumnType<IBeneficiaire>[] {
  const canImpersonate = !!(user?.isAdmin && env.REACT_APP_ENVIRONMENT !== "production");

  const columnMap = new Map<string, ColumnType<IBeneficiaire>>();

  // 1. Bénéficiaire
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.NOM, {
    title: "Bénéficiaire",
    dataIndex: "nom",
    fixed: "left",
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.NOM,
    render: (_value, record) => <NomCell record={record} searchedNom={filter.nom} />,
    sortDirections: ["ascend", "descend"],
    sorter: true,
    defaultSortOrder: "ascend",
    sortOrder: ascToAscend(filter?.["order[nom]"]),
    ...FilterProps<FiltreBeneficiaire>("nom", filter, setFilter),
    filteredValue: filter?.nom ? [filter?.nom] : null,
    className: "pointer",
    onCell: (record) => ({ onClick: () => onBeneficiaireSelected(record) }),
  } as ColumnType<IBeneficiaire>);

  // 2. Composantes
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.COMPOSANTE, {
    title: "Composantes",
    dataIndex: "composantes",
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.COMPOSANTE,
    render: (_value, record) => <ComposantesCell record={record} />,
  });

  // 3. Inscription
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.INSCRIPTION, {
    title: "Inscription",
    dataIndex: "inscription",
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.INSCRIPTION,
    render: (_value, record) => <InscriptionCell record={record} />,
  });

  // 4. Tags
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.TAGS, {
    title: "Tags",
    dataIndex: "tags",
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.TAGS,
    render: (_value, record) => (
      <div>
        {record.tags?.map((tag) => (
          <UtilisateurTag
            tagId={tag}
            key={tag}
            utilisateurId={record.uid as string}
            className="mr-05"
          />
        ))}
      </div>
    ),
  });

  // 5. Profils et avis santé (Gestionnaires uniquement)
  if (user?.isGestionnaire) {
    columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.PROFILS, {
      title: "Profils actifs",
      dataIndex: "profils",
      key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.PROFILS,
      render: (values?: string[]) =>
        values?.map((p) => (
          <BeneficiaireProfilItem key={p} profilBeneficiaire={p} masquerSiInactif />
        )),
    });

    columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.ETAT_AVIS_ESE, {
      title: `Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"}`,
      dataIndex: "etatAvisEse",
      key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.ETAT_AVIS_ESE,
      className: "text-center",
      render: (value: string) => (
        <BeneficiaireAvisEseAvatar
          etatAvisEse={value as EtatAvisEse}
          showLabel={false}
          direction="vertical"
          className="fs-12"
        />
      ),
    });
  }

  // 6. Décision d'établissement
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.DECISION_ETAB, {
    title: "Décision étab.",
    dataIndex: "IDecisionEtablissement",
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.DECISION_ETAB,
    className: "text-center",
    render: (_value, record) =>
      record.decisionAmenagementAnneeEnCours ? (
        <DecisionEtablissementAvatar
          decisionEtab={record.decisionAmenagementAnneeEnCours}
          showLabel={false}
          direction="vertical"
          className="fs-12"
        />
      ) : null,
  });

  // 7. Chargé•es d'accompagnement
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.GESTIONNAIRE, {
    title: <span aria-label="Chargés d'accompagnement">Chargé•es d'acc.</span>,
    dataIndex: "gestionnaire[]",
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.GESTIONNAIRE,
    render: (_value, record) => <ChargesAccompagnementsItem utilisateur={record} />,
  });

  // 8. Email
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.EMAIL, {
    title: "Email",
    dataIndex: "email",
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.EMAIL,
    width: 200,
    render: (_value, record) => <CopyableTextCell text={record.email} width={200} wrap />,
  });

  // 9. Numéro étudiant
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.NUM_ETUDIANT, {
    title: "Numéro étudiant",
    dataIndex: "numeroEtudiant",
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.NUM_ETUDIANT,
    render: (_value, record) => <CopyableTextCell text={record.numeroEtudiant} />,
  });

  // 10. Statut
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.STATUT, {
    title: "Statut",
    dataIndex: "statut",
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.STATUT,
    render: (_value, record) => record.statutEtudiant || <MinusOutlined />,
  });

  // 11. Colonnes complémentaires dynamiques
  colonnesComplementaires?.forEach((cle) => {
    if (columnMap.has(cle)) return;
    columnMap.set(cle, {
      title: cle,
      key: cle,
      render: (_value, record) => {
        const info = record.infosComplementaires?.find((i) => i.libelle === cle);
        return <CopyableTextCell text={info?.valeur} />;
      },
    });
  });

  // 12. Actions
  columnMap.set(BENEFICIAIRE_TABLE_COLUMNS_KEYS.ACTIONS, {
    key: BENEFICIAIRE_TABLE_COLUMNS_KEYS.ACTIONS,
    filteredValue: null,
    title: <span className="sr-only">Actions</span>,
    className: "text-right",
    width: 160,
    render: (_value, record) => (
      <ActionsCell
        record={record}
        canImpersonate={canImpersonate}
        isGestionnaire={user?.isGestionnaire}
        onBeneficiaireSelected={onBeneficiaireSelected}
        onImpersonate={onImpersonate}
      />
    ),
  });

  if (colonnesVisibles) {
    return colonnesVisibles
      .map((key) => columnMap.get(key))
      .filter((col): col is ColumnType<IBeneficiaire> => col !== undefined);
  }

  return Array.from(columnMap.values());
}
