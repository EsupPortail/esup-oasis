/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import {
  IAmenagement,
  IComposante,
  ICategorieAmenagement,
  ITag,
  ITypeAmenagement,
  ITypeSuiviAmenagement,
  IUtilisateur,
  PREFETCH_CATEGORIES_AMENAGEMENTS,
  PREFETCH_COMPOSANTES,
  PREFETCH_TAGS,
  PREFETCH_TYPES_AMENAGEMENTS,
  PREFETCH_TYPES_SUIVI_AMENAGEMENTS,
} from "@api";
import { useState } from "react";
import { useApi } from "@context/api/ApiProvider";
import { FiltreAmenagement, filtreAmenagementToApi } from "@controls/Table/AmenagementTableLayout";
import { getDomaineAmenagement, RoleValues } from "@lib";
import dayjs from "dayjs";
import { ModeAffichageAmenagement } from "@routes/gestionnaire/beneficiaires/Amenagements";
import { env } from "@/env";
import CsvExportButton from "@controls/Table/Export/CsvExportButton";
import { getInscriptionsHeaders } from "@controls/Table/Export/inscriptionsExportHeaders";
import { useExportInscriptions } from "@controls/Table/Export/useExportInscriptions";
import { formaterInscriptionsExport, PorteeExportInscriptions } from "@lib/inscription";

const getHeaders = (portee: PorteeExportInscriptions) => [
  { label: "Nom", key: "nom" },
  { label: "Prénom", key: "prenom" },
  { label: "Email", key: "email" },
  { label: "Numéro étudiant", key: "numeroEtudiant" },
  ...getInscriptionsHeaders(portee),
  { label: "Domaine", key: "domaine" },
  { label: "Catégorie", key: "categorie" },
  { label: "Type", key: "type" },
  { label: "Semestre 1", key: "semestre1" },
  { label: "Semestre 2", key: "semestre2" },
  { label: "Début", key: "debut" },
  { label: "Fin", key: "fin" },
  { label: "Commentaire", key: "commentaire" },
  { label: "Suivi", key: "suivi" },
  { label: "Chargé•e d'accompagnement", key: "chargeAccompagnement" },
  { label: `Avis ${env.REACT_APP_ESPACE_SANTE_ABV || "santé"}`, key: "avisESE" },
  { label: "Tags", key: "tags" },
];

export function getAmenagementsData(
  amenagements: IAmenagement[],
  categories: ICategorieAmenagement[] | undefined,
  types: ITypeAmenagement[] | undefined,
  suivis: ITypeSuiviAmenagement[] | undefined,
  cas: IUtilisateur[] | undefined,
  tags: ITag[] | undefined,
  composantes: IComposante[] | undefined,
  portee: PorteeExportInscriptions,
) {
  return amenagements.map((amenagement) => {
    const beneficiaire = amenagement.beneficiaire;

    return {
      key: amenagement["@id"],
      "@id": amenagement["@id"],
      nom: beneficiaire?.nom?.toLocaleUpperCase(),
      prenom: beneficiaire?.prenom,
      email: beneficiaire?.email,
      numeroEtudiant: beneficiaire?.numeroEtudiant,
      domaine:
        getDomaineAmenagement(
          types?.find((ta) => ta["@id"] === amenagement.typeAmenagement),
        )?.singulier.replaceAll('"', '""') || "Non renseigné",
      categorie: categories?.find(
        (c) =>
          c["@id"] ===
          types
            ?.find((ta) => ta["@id"] === amenagement.typeAmenagement)
            ?.categorie.replaceAll('"', '""'),
      )?.libelle,
      type: types
        ?.find((ta) => ta["@id"] === amenagement.typeAmenagement)
        ?.libelle.replaceAll('"', '""'),
      semestre1: amenagement.semestre1 ? "Oui" : "Non",
      semestre2: amenagement.semestre2 ? "Oui" : "Non",
      debut: amenagement.debut ? dayjs(amenagement.debut).format("DD/MM/YYYY") : undefined,
      fin: amenagement.fin ? dayjs(amenagement.fin).format("DD/MM/YYYY") : undefined,
      commentaire: amenagement.commentaire?.replaceAll('"', '""'),
      suivi: suivis?.find((s) => s["@id"] === amenagement.suivi)?.libelle?.replaceAll('"', '""'),
      ...formaterInscriptionsExport(beneficiaire?.inscriptions, portee, composantes),
      chargeAccompagnement: (beneficiaire?.gestionnairesActifs || [])
        .map((g) => {
          const ca = cas?.find((c) => c["@id"] === g);
          return ca ? `${ca.nom?.toLocaleUpperCase()} ${ca.prenom}` : "";
        })
        .join(", "),
      avisESE: beneficiaire?.etatAvisEse,
      tags: (beneficiaire?.tags || [])
        ?.map((tag) => tags?.find((t) => t["@id"] === tag))
        .map((tag) => tag?.libelle?.replaceAll('"', '""'))
        .join(", "),
    };
  });
}

interface TableAmenagementsExportProps {
  filtreAmenagement: FiltreAmenagement;
}

export default function AmenagementTableExport({
  filtreAmenagement,
}: TableAmenagementsExportProps) {
  const [{ exportKey, exportSubmit, prevFilter }, setExportState] = useState({
    exportKey: 0,
    exportSubmit: false,
    prevFilter: filtreAmenagement,
  });

  if (prevFilter !== filtreAmenagement) {
    setExportState((prev) => ({
      exportKey: prev.exportKey + 1,
      exportSubmit: false,
      prevFilter: filtreAmenagement,
    }));
  }

  const { data: categories } = useApi().useGetFullCollection({
    ...PREFETCH_CATEGORIES_AMENAGEMENTS,
    enabled: exportSubmit,
  });
  const { data: types } = useApi().useGetFullCollection({
    ...PREFETCH_TYPES_AMENAGEMENTS,
    enabled: exportSubmit,
  });
  const { data: suivis } = useApi().useGetFullCollection({
    ...PREFETCH_TYPES_SUIVI_AMENAGEMENTS,
    enabled: exportSubmit,
  });
  const { data: cas } = useApi().useGetFullCollection({
    path: "/roles/{roleId}/utilisateurs",
    parameters: { roleId: `/roles/${RoleValues.ROLE_GESTIONNAIRE}` },
    enabled: exportSubmit,
  });
  const { data: tags } = useApi().useGetFullCollection({
    ...PREFETCH_TAGS,
    enabled: exportSubmit,
  });

  const { data: composantes } = useApi().useGetFullCollection({
    ...PREFETCH_COMPOSANTES,
    enabled: exportSubmit,
  });

  const refDataReady = !!(
    categories?.items &&
    types?.items &&
    suivis?.items &&
    cas?.items &&
    tags?.items &&
    composantes?.items
  );

  const { portee, demanderPortee, modalPortee } = useExportInscriptions();

  return (
    <>
      <CsvExportButton<"/amenagements">
        key={exportKey}
        path="/amenagements"
        itemsPerPage={500}
        query={filtreAmenagementToApi(filtreAmenagement, ModeAffichageAmenagement.ParAmenagement)}
        headers={getHeaders(portee)}
        filename="amenagements"
        getData={(items) =>
          getAmenagementsData(
            items,
            categories?.items,
            types?.items,
            suivis?.items,
            cas?.items,
            tags?.items,
            composantes?.items,
            portee,
          )
        }
        ready={refDataReady}
        onBeforeStart={demanderPortee}
        onStart={() => setExportState((prev) => ({ ...prev, exportSubmit: true }))}
      />
      {modalPortee}
    </>
  );
}
