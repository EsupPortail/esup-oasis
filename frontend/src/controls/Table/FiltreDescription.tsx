/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Button, List, Modal, Tag, Tooltip } from "antd";
import { FiltreBeneficiaire } from "@controls/Table/BeneficiaireTable";
import { CampusItem } from "@controls/Items/CampusItem";
import { CompetenceItem } from "@controls/Items/CompetenceItem";
import { ComposanteItem } from "@controls/Items/ComposanteItem";
import { DisciplineItem } from "@controls/Items/DisciplineItem";
import { FormationItem } from "@controls/Items/FormationItem";
import { GestionnaireItem } from "@controls/Items/GestionnaireItem";
import { ProfilItem } from "@controls/Items/ProfilItem";
import { SuiviAmenagementItem } from "@controls/Items/SuiviAmenagementItem";
import { TypeDemandeItem } from "@controls/Items/TypeDemandeItem";
import { TypeEvenementItem } from "@controls/Items/TypeEvenementItem";
import { UtilisateurTag } from "@controls/Tags/UtilisateurTag";
import Icon from "@ant-design/icons";
import FilterMore from "@/assets/images/filter-more.svg?react";
import { FiltreIntervenant } from "@controls/Table/IntervenantTable";
import { FiltreDemande } from "@controls/Table/DemandeTable";
import { EtatDemandeAvatar } from "@controls/Avatars/EtatDemandeAvatar";
import type { ButtonType } from "antd/es/button/buttonHelpers";
import { FiltreAmenagement } from "@controls/Table/AmenagementTableLayout";
import { CategorieAmenagementTag } from "@controls/Avatars/CategorieAmenagementTag";
import { TypeAmenagementTag } from "@controls/Avatars/TypeAmenagementTag";
import { DOMAINES_AMENAGEMENTS_INFOS } from "@lib";
import { env } from "@/env";
import dayjs from "dayjs";

export type FiltreDecrivable =
  FiltreBeneficiaire | FiltreIntervenant | FiltreDemande | FiltreAmenagement;

type FiltreDescriptionType = {
  key: string;
  label: string;
  render?: (
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    value: any,
    filtre?: FiltreDecrivable,
  ) => React.ReactElement | React.ReactElement[] | string;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  ignore?: (value: any, filtre?: FiltreDecrivable) => boolean;
  /** Si définie, l'entrée est virtuelle (non liée à une clé du filtre) et s'affiche quand condition est vraie */
  condition?: (filtre: FiltreDecrivable) => boolean;
};

const filtreDescriptionItem: FiltreDescriptionType[] = [
  {
    key: "avisEse",
    label: `Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"}`,
    render: (value: string) => <Tag className="mb-1">{value}</Tag>,
  },
  {
    key: "campagne.typeDemande[]",
    label: "Type de demande",
    render: (value: string[]) =>
      value.map((v) => (
        <TypeDemandeItem typeDemandeId={v} showAvatar={false} className="mr-1 mb-1" key={v} />
      )),
  },
  {
    key: "categorie[]",
    label: "Catégories aménagements",
    render: (value: string[]) =>
      value.map((v) => <CategorieAmenagementTag key={v} categorieId={v} />),
  },
  {
    key: "composante[]",
    label: "Composantes",
    render: (value: string[]) =>
      value.map((v) => <ComposanteItem className="mb-1" key={v} composanteId={v} />),
  },
  {
    key: "demandeur.nom",
    label: "Nom",
    render: (value: string) => <Tag className="mb-1">{value}</Tag>,
  },
  {
    key: "domaine",
    label: "Domaine aménagement",
    ignore: (value: string) => value === "Tous",
    render: (value: string) =>
      value === "Tous" ? (
        <Tag className="mb-1">Tous les domaines</Tag>
      ) : (
        <Tag className="mb-1">{DOMAINES_AMENAGEMENTS_INFOS[value]?.singulier}</Tag>
      ),
  },
  {
    key: "discipline[]",
    label: "Disciplines",
    render: (value: string[]) =>
      value.map((v) => <DisciplineItem className="mb-1" disciplineId={v} key={v} />),
  },
  {
    key: "etat[]",
    label: "État demande",
    render: (value: string[]) =>
      value.map((v) => <EtatDemandeAvatar className="mb-1" etatDemandeId={v} key={v} />),
  },
  {
    key: "etat",
    label: "État demande",
    render: (value: string) => (
      <EtatDemandeAvatar className="mb-1" etatDemandeId={value} key={value} />
    ),
  },
  {
    key: "formation[]",
    label: "Formations",
    render: (value: string[]) =>
      value.map((v) => <FormationItem className="mb-1" formationId={v} />),
  },
  {
    key: "gestionnaire[]",
    label: "Chargé•e d'accompagnement",
    render: (value: string[]) =>
      value.map((v) => <GestionnaireItem key={v} gestionnaireId={v} className="mr-1 mb-1" />),
  },
  {
    key: "intervenant.campuses[]",
    label: "Campus",
    render: (value: string[]) =>
      value.map((v) => <CampusItem key={v} campusId={v} className="mr-1 mb-1" />),
  },
  {
    key: "intervenant.competences[]",
    label: "Compétences",
    render: (value: string[]) =>
      value.map((v) => (
        <CompetenceItem key={v} competenceId={v} className="mr-1 mb-1" showAvatar={false} />
      )),
  },
  {
    key: "intervenant.typesEvenements[]",
    label: "Types événements",
    render: (value: string[]) =>
      value.map((v) => (
        <TypeEvenementItem key={v} typeEvenementId={v} showAvatar={false} className="mr-1 mb-1" />
      )),
  },
  {
    key: "intervenantArchive",
    label: "Statut intervenant",
    ignore: (value: boolean | "undefined") => value === "undefined",
    render: (value: boolean) => {
      if (value) {
        return <Tag className="mb-1">Archivé</Tag>;
      }

      return <Tag className="mb-1">Actif</Tag>;
    },
  },
  {
    key: "filtreBeneficiaire[date]",
    label: "Validité du profil",
    render: (value: string) => (
      <Tag className="mb-1">Valide à la date du {dayjs(value).format("DD/MM/YYYY")}</Tag>
    ),
  },
  {
    key: "filtreBeneficiaire[apres]",
    label: "Validité du profil à partir du",
    render: (value: string) => <Tag className="mb-1">{dayjs(value).format("DD/MM/YYYY")}</Tag>,
  },
  {
    key: "filtreBeneficiaire[avant]",
    label: "Validité du profil jusqu'au",
    ignore: (_value, filtre) => {
      const f = filtre as FiltreBeneficiaire;
      return (
        f?.["filtreBeneficiaire[apres]"] === undefined &&
        f?.["filtreBeneficiaire[date]"] === undefined
      );
    },
    render: (value: string) => <Tag className="mb-1">{dayjs(value).format("DD/MM/YYYY")}</Tag>,
  },
  {
    key: "filtreBeneficiaire[nimportequand]",
    label: "Validité du profil",
    condition: (filtre) => {
      const f = filtre as FiltreBeneficiaire;
      return (
        f["filtreBeneficiaire[avant]"] !== undefined &&
        f["filtreBeneficiaire[apres]"] === undefined &&
        f["filtreBeneficiaire[date]"] === undefined
      );
    },
    render: () => <Tag className="mb-1">Sans restriction de date</Tag>,
  },
  {
    key: "beneficiaires.avecAccompagnement",
    label: "Accompagnement",
    render: (value: boolean) => (
      <Tag className="mb-1">{value ? "Avec accompagnement" : "Sans accompagnement"}</Tag>
    ),
  },
  { key: "nom", label: "Nom", render: (value: string) => <Tag>{value}</Tag> },
  {
    key: "profil",
    label: "Profil",
    render: (value: string) => <ProfilItem key={value} profil={value} />,
  },
  {
    key: "restreindreColonnes",
    label: "Restreindre les colonnes",
    ignore: (value: boolean) => !value,
    render: (value: boolean | "undefined") => (value ? <Tag>Oui</Tag> : <Tag>Non</Tag>),
  },
  {
    key: "suivis[]",
    label: "Suivis aménagements",
    render: (value: string[]) =>
      value.map((v) => <SuiviAmenagementItem key={v} suiviId={v} className="mb-1" />),
  },
  {
    key: "tags[]",
    label: "Tags",
    render: (value: string[]) =>
      value.map((v) => <UtilisateurTag key={v} tagId={v} className="mb-1" />),
  },
  {
    key: "type[]",
    label: "Types aménagements",
    render: (value: string[]) => value.map((v) => <TypeAmenagementTag key={v} typeId={v} />),
  },
  {
    key: "type.categorie[]",
    label: "Catégories aménagements",
    render: (value: string[]) =>
      value.map((v) => <CategorieAmenagementTag key={v} categorieId={v} />),
  },
];

const filtreDescriptionItemByKey = new Map(filtreDescriptionItem.map((item) => [item.key, item]));

function FiltreDescriptionContenu(props: { filtre: FiltreDecrivable }) {
  const filtresToExplain = Object.entries(props.filtre)
    // filtres vides
    .filter(
      ([, value]) =>
        value !== undefined &&
        value !== "undefined" &&
        value !== null &&
        value !== "" &&
        !(Array.isArray(value) && value.length === 0),
    )
    // filtres à expliquer
    .filter(([key, value]) => {
      const item = filtreDescriptionItemByKey.get(key);
      return item?.render && (!item.ignore || !item.ignore(value, props.filtre));
    });

  const virtualsToExplain = filtreDescriptionItem.filter(
    (item) =>
      item.condition &&
      !Object.prototype.hasOwnProperty.call(props.filtre, item.key) &&
      item.condition(props.filtre),
  );

  const allEntries: { key: string; label: string; element: React.ReactNode }[] = [
    ...filtresToExplain.map(([key, value]) => {
      const item = filtreDescriptionItemByKey.get(key)!;
      return {
        key,
        label: item.label,
        element: item.render ? item.render(value, props.filtre) : value,
      };
    }),
    ...virtualsToExplain.map((item) => ({
      key: item.key,
      label: item.label,
      element: item.render?.(undefined, props.filtre),
    })),
  ];

  return (
    <div>
      <List className="bg-white" size="small" bordered>
        {allEntries.map(({ key, label, element }) => (
          <List.Item key={key}>
            <span className="semi-bold">{label}</span> : {element}
          </List.Item>
        ))}
        {allEntries.length === 0 && <List.Item>Aucun filtre</List.Item>}
      </List>
    </div>
  );
}

export default function FiltreDescription(props: {
  filtre: FiltreDecrivable;
  as?: "div" | "modal";
  type?: ButtonType;
  tooltip?: string;
}) {
  const [visible, setVisible] = React.useState<boolean>(false);
  if (props.as === "modal") {
    return (
      <>
        <Tooltip title={props.tooltip ?? "Décrire le filtre"}>
          <Button
            type={props.type}
            icon={<Icon component={FilterMore} aria-label="Expliquer le filtre" />}
            onClick={() => setVisible(true)}
          />
        </Tooltip>
        <Modal
          title="Description du filtre"
          open={visible}
          onOk={() => setVisible(false)}
          onCancel={() => setVisible(false)}
          footer={null}
        >
          <FiltreDescriptionContenu filtre={props.filtre} />
        </Modal>
      </>
    );
  }

  return <FiltreDescriptionContenu filtre={props.filtre} />;
}
