/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import {
  IBeneficiaire,
  IComposante,
  ITag,
  IUtilisateur,
  PREFETCH_COMPOSANTES,
  PREFETCH_TAGS,
} from "@api";
import { useMemo, useState } from "react";
import { useApi } from "@context/api/ApiProvider";
import { FiltreBeneficiaire } from "@controls/Table/BeneficiaireTable";
import { env } from "@/env";
import CsvExportButton from "@controls/Table/Export/CsvExportButton";
import { composantesFromInscriptions } from "@lib/Inscriptions";
import { BENEFICIAIRE_TABLE_COLUMNS_KEYS } from "@controls/Table/BeneficiaireTableColumns";

const {
  NOM,
  COMPOSANTE,
  INSCRIPTION,
  TAGS,
  PROFILS,
  ETAT_AVIS_ESE,
  DECISION_ETAB,
  GESTIONNAIRE,
  EMAIL,
  NUM_ETUDIANT,
  STATUT,
  ACTIONS,
} = BENEFICIAIRE_TABLE_COLUMNS_KEYS;

const DEFAULT_COLUMNS: string[] = [
  NOM,
  EMAIL,
  NUM_ETUDIANT,
  COMPOSANTE,
  INSCRIPTION,
  GESTIONNAIRE,
  TAGS,
  ETAT_AVIS_ESE,
];

// Colonnes sans entrée (actions, profils) : non exportées. Colonnes inconnues : informations complémentaires.
const COLUMN_HEADERS_MAP: Record<string, { label: string; key: string }[]> = {
  [NOM]: [
    { label: "Nom", key: "nom" },
    { label: "Prénom", key: "prenom" },
  ],
  [COMPOSANTE]: [{ label: "Composantes", key: "composantes" }],
  [INSCRIPTION]: [{ label: "Formations", key: "formations" }],
  [TAGS]: [{ label: "Tags", key: "tags" }],
  [ETAT_AVIS_ESE]: [{ label: `Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"}`, key: "avisESE" }],
  [DECISION_ETAB]: [{ label: "Décision étab.", key: "decisionEtab" }],
  [GESTIONNAIRE]: [{ label: "Gestionnaires", key: "gestionnaires" }],
  [EMAIL]: [{ label: "Email", key: "email" }],
  [NUM_ETUDIANT]: [{ label: "Numéro étudiant", key: "numeroEtudiant" }],
  [STATUT]: [{ label: "Statut", key: "statut" }],
  [PROFILS]: [],
  [ACTIONS]: [],
};

function getBeneficiairesData(
  beneficiaires: IBeneficiaire[],
  composantes: IComposante[] | undefined,
  gestionnaires: IUtilisateur[] | undefined,
  tags: ITag[] | undefined,
) {
  return beneficiaires.map((beneficiaire) => {
    const infosCompData: Record<string, string> = {};
    beneficiaire.infosComplementaires?.forEach((info) => {
      if (info.libelle) {
        infosCompData[info.libelle] = info.valeur ?? "";
      }
    });

    return {
      key: beneficiaire["@id"],
      "@id": beneficiaire["@id"],
      nom: beneficiaire.nom?.toLocaleUpperCase(),
      prenom: beneficiaire.prenom,
      email: beneficiaire.email,
      numeroEtudiant: beneficiaire.numeroEtudiant,
      statut: beneficiaire.statutEtudiant,
      composantes: composantesFromInscriptions(beneficiaire.inscriptions || [], composantes || [])
        .map((composante) => composante?.libelle?.replaceAll('"', '""'))
        .join(", "),
      formations: beneficiaire.inscriptions
        ?.map((inscription) => inscription.formation)
        ?.filter((formation) => !!formation)
        .map((formation) => {
          const libelle = formation.libelle?.replaceAll('"', '""');
          return formation.codeExterne ? `[${formation.codeExterne}] ${libelle}` : libelle;
        })
        .join(", "),
      gestionnaires: beneficiaire.gestionnairesActifs
        ?.map((gestionnaire) => gestionnaires?.find((g) => g["@id"] === gestionnaire))
        .filter((gestionnaire) => !!gestionnaire)
        .map((gestionnaire) => `${gestionnaire.nom?.toLocaleUpperCase()} ${gestionnaire.prenom}`)
        .join(", "),
      avisESE: beneficiaire.etatAvisEse,
      decisionEtab: beneficiaire.decisionAmenagementAnneeEnCours?.etat || "",
      tags: beneficiaire.tags
        ?.map((tag) => tags?.find((t) => t["@id"] === tag))
        .filter((tag) => !!tag)
        .map((tag) => tag.libelle?.replaceAll('"', '""'))
        .join(", "),
      ...infosCompData,
    };
  });
}

interface TableBeneficiairesExportProps {
  filtreBeneficiaire: FiltreBeneficiaire;
  colonnesVisibles?: string[];
}

export default function BeneficiaireTableExport({
  filtreBeneficiaire,
  colonnesVisibles,
}: TableBeneficiairesExportProps) {
  const [{ exportKey, exportSubmit, prevFilter }, setExportState] = useState({
    exportKey: 0,
    exportSubmit: false,
    prevFilter: filtreBeneficiaire,
  });

  if (prevFilter !== filtreBeneficiaire) {
    setExportState((prev) => ({
      exportKey: prev.exportKey + 1,
      exportSubmit: false,
      prevFilter: filtreBeneficiaire,
    }));
  }

  const { data: composantes } = useApi().useGetFullCollection({
    ...PREFETCH_COMPOSANTES,
    enabled: exportSubmit,
  });
  const { data: gestionnaires } = useApi().useGetFullCollection({
    path: "/roles/{roleId}/utilisateurs",
    parameters: { roleId: "/roles/ROLE_PLANIFICATEUR" },
    enabled: exportSubmit,
  });
  const { data: tags } = useApi().useGetFullCollection({
    ...PREFETCH_TAGS,
    enabled: exportSubmit,
  });

  const headers = useMemo(
    () =>
      (colonnesVisibles?.length ? colonnesVisibles : DEFAULT_COLUMNS).flatMap(
        (colKey) => COLUMN_HEADERS_MAP[colKey] || [{ label: colKey, key: colKey }],
      ),
    [colonnesVisibles],
  );

  const refDataReady = !!(composantes?.items && gestionnaires?.items && tags?.items);

  return (
    <CsvExportButton<"/beneficiaires">
      key={exportKey}
      path="/beneficiaires"
      itemsPerPage={200}
      query={{ ...filtreBeneficiaire }}
      headers={headers}
      filename="beneficiaires"
      getData={(items) =>
        getBeneficiairesData(items, composantes?.items, gestionnaires?.items, tags?.items)
      }
      ready={refDataReady}
      onStart={() => setExportState((prev) => ({ ...prev, exportSubmit: true }))}
    />
  );
}
