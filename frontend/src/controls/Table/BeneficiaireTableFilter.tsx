/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { useAuth } from "../../auth/AuthProvider";
import { useApi } from "../../context/api/ApiProvider";
import {
   PREFETCH_COMPOSANTES,
   PREFETCH_FORMATIONS,
   PREFETCH_PROFILS,
   PREFETCH_TAGS,
} from "../../api/ApiPrefetchHelpers";
import { Badge, Col, Collapse, Input, Row, Segmented, Select, Space, Tooltip } from "antd";
import { CheckCircleFilled, FilterOutlined, HourglassOutlined } from "@ant-design/icons";
import { BENEFICIAIRE_PROFIL_A_DETERMINER, NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { FiltresFavoris } from "./FiltresFavoris";
import { FILTRE_BENEFICIAIRE_DEFAULT, FiltreBeneficiaire } from "./BeneficiaireTable";
import { EtatAvisEse } from "../Avatars/BeneficiaireAvisEseAvatar";
import { FiltreFavoriDropDown } from "./FiltreFavoriDropDown";
import { EtatDecisionEtablissement } from "../Avatars/DecisionEtablissementAvatar";
import { env } from "../../env";

function booleanToString(value: boolean | undefined): string | undefined {
   if (value === undefined) return "undefined";
   return value ? "true" : "false";
}

function stringToBoolean(value: string | undefined): boolean | undefined {
   if (value === "undefined") return undefined;
   if (value === "true") return true;
   if (value === "false") return false;
   return undefined;
}

export function BeneficiaireTableFilter(props: {
   filtreBeneficiaire: FiltreBeneficiaire;
   setFiltreBeneficiaire: React.Dispatch<React.SetStateAction<FiltreBeneficiaire>>;
}) {
   const auth = useAuth();
   const { data: profils } = useApi().useGetCollection({
      ...PREFETCH_PROFILS,
      enabled: auth.user?.isGestionnaire,
   });
   const { data: stats } = useApi().useGetItem({
      path: "/statistiques",
      query: {
         utilisateur: auth.user?.["@id"] as string,
      },
      enabled:
         // Les bénéficiaires n'ont pas accès aux stats
         // Bugfix lors de l'impersonate
         !!auth.user?.["@id"] &&
         (auth.user?.isPlanificateur || auth.user?.isIntervenant) &&
         !auth.impersonate,
   });
   const { data: composantes } = useApi().useGetCollection(PREFETCH_COMPOSANTES);
   const { data: formations } = useApi().useGetCollection(PREFETCH_FORMATIONS);
   const { data: tags } = useApi().useGetCollection(PREFETCH_TAGS);

   // Récupération de la liste des gestionnaires (hors renforts)
   const { data: gestionnaires, isFetching: isFetchingGestionnaires } =
      useApi().useGetCollectionPaginated({
         path: "/roles/{roleId}/utilisateurs",
         parameters: { roleId: "/roles/ROLE_GESTIONNAIRE" },
         page: 1,
         itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
         query: { "order[nom]": "asc" },
         enabled: auth.user?.isPlanificateur,
      });

   return (
      <Collapse
         accordion
         className="mb-3"
         items={[
            {
               key: "filter_save",
               label: (
                  <>
                     <FiltreFavoriDropDown
                        className="float-right mt-05"
                        setFiltre={props.setFiltreBeneficiaire}
                        filtreType="filtresBeneficiaire"
                     />
                     Filtres sauvegardés
                  </>
               ),
               children: (
                  <FiltresFavoris
                     filtre={props.filtreBeneficiaire}
                     setFiltre={props.setFiltreBeneficiaire}
                     filtreType="filtresBeneficiaire"
                     defaultFilter={FILTRE_BENEFICIAIRE_DEFAULT}
                  />
               ),
            },
            {
               key: "filters",
               label: (
                  <>
                     <FilterOutlined className="float-right" style={{ marginTop: 4 }} aria-hidden />
                     Filtres complémentaires
                     {auth.user?.isGestionnaire &&
                     stats?.nbBeneficiairesIncomplets &&
                     stats.nbBeneficiairesIncomplets > 0 ? (
                        <Tooltip title="Bénéficiaires avec profil à renseigner">
                           <Badge className="ml-2" count={stats.nbBeneficiairesIncomplets} />
                        </Tooltip>
                     ) : null}
                  </>
               ),
               children: (
                  <>
                     <Row gutter={[16, 16]}>
                        <Col xs={24} sm={24} md={6}>
                           Nom du bénéficiaire
                        </Col>
                        <Col xs={24} sm={24} md={18}>
                           <Input
                              allowClear
                              placeholder="Nom du bénéficiaire"
                              value={props.filtreBeneficiaire.nom}
                              onChange={(e) => {
                                 props.setFiltreBeneficiaire((prev) => ({
                                    ...prev,
                                    nom: e.target.value,
                                    page: 1,
                                 }));
                              }}
                           />
                        </Col>

                        <Col xs={24} sm={24} md={6}>
                           Accompagnement {env.REACT_APP_SERVICE}
                        </Col>
                        <Col xs={24} sm={24} md={18}>
                           <Segmented
                              style={{ overflowX: "auto", maxWidth: "100%" }}
                              value={booleanToString(
                                 props.filtreBeneficiaire["beneficiaires.avecAccompagnement"],
                              )}
                              onChange={(value) => {
                                 props.setFiltreBeneficiaire((prev) => ({
                                    ...prev,
                                    "beneficiaires.avecAccompagnement": stringToBoolean(value),
                                    page: 1,
                                 }));
                              }}
                              options={[
                                 {
                                    label: "Tous",
                                    value: "undefined",
                                 },
                                 {
                                    label: "Avec accompagnement",
                                    value: "true",
                                 },
                                 {
                                    label: "Sans accompagnement",
                                    value: "false",
                                 },
                              ]}
                           />
                        </Col>

                        <Col xs={24} sm={24} md={6}>
                           <span aria-label="Chargés d'accompagnement">
                              Chargé•es d'accompagnement
                           </span>
                        </Col>
                        <Col xs={24} sm={24} md={18}>
                           <Select
                              placeholder="Tous les chargés d'accompagnement"
                              mode="tags"
                              allowClear
                              loading={isFetchingGestionnaires}
                              className="w-100"
                              options={gestionnaires?.items.map((g) => ({
                                 label: `${g.nom?.toLocaleUpperCase()} ${g.prenom}`,
                                 value: g["@id"],
                              }))}
                              onChange={(value) => {
                                 props.setFiltreBeneficiaire((prev) => ({
                                    ...prev,
                                    nomGestionnaire: undefined,
                                    "gestionnaire[]": value === "undefined" ? undefined : value,
                                    page: 1,
                                 }));
                              }}
                           />
                        </Col>

                        {auth.user?.isGestionnaire && (
                           <>
                              <Col xs={24} sm={24} md={6}>
                                 <Space direction="vertical" size={0}>
                                    <span>Profils</span>
                                    <div
                                       className={`legende ${
                                          (stats?.nbBeneficiairesIncomplets || 0) > 1
                                             ? "text-danger"
                                             : ""
                                       }`}
                                    >
                                       {stats?.nbBeneficiairesIncomplets} profil
                                       {(stats?.nbBeneficiairesIncomplets || 0) > 1 && "s"} à
                                       renseigner
                                    </div>
                                 </Space>
                              </Col>
                              <Col xs={24} sm={24} md={18}>
                                 <Select
                                    allowClear
                                    className="w-100"
                                    placeholder="Tous les profils"
                                    value={props.filtreBeneficiaire.profil}
                                    onChange={(value) => {
                                       props.setFiltreBeneficiaire((prev) => ({
                                          ...prev,
                                          profil: value === "undefined" ? undefined : value,
                                          page: 1,
                                       }));
                                    }}
                                    options={[
                                       {
                                          label: "Profils à renseigner",
                                          value: BENEFICIAIRE_PROFIL_A_DETERMINER,
                                       },
                                       ...(profils?.items || [])
                                          .filter(
                                             (p) =>
                                                p.actif &&
                                                p["@id"] !== BENEFICIAIRE_PROFIL_A_DETERMINER,
                                          )
                                          .map((profil) => ({
                                             label: profil.libelle,
                                             value: profil["@id"],
                                          })),
                                    ]}
                                 />
                              </Col>
                           </>
                        )}

                        <Col xs={24} sm={24} md={6}>
                           Tags
                        </Col>
                        <Col xs={24} sm={24} md={18}>
                           <Select
                              allowClear
                              mode="tags"
                              className="w-100"
                              placeholder="Tous les tags"
                              value={props.filtreBeneficiaire["tags[]"]}
                              onChange={(value) => {
                                 props.setFiltreBeneficiaire((prev) => ({
                                    ...prev,
                                    "tags[]": value as string[],
                                    page: 1,
                                 }));
                              }}
                              options={(tags?.items || []).map((c) => ({
                                 label: c.libelle,
                                 value: c["@id"],
                              }))}
                              optionFilterProp="label"
                           />
                        </Col>

                        <Col xs={24} sm={24} md={6}>
                           Avis {env.REACT_APP_ESPACE_SANTE_ABV || "santé"}
                        </Col>
                        <Col xs={24} sm={24} md={18}>
                           <Segmented
                              style={{ overflowX: "auto", maxWidth: "100%" }}
                              onChange={(value) => {
                                 props.setFiltreBeneficiaire((prev) => ({
                                    ...prev,
                                    etatAvisEse:
                                       value === "undefined" ? undefined : (value as EtatAvisEse),
                                    page: 1,
                                 }));
                              }}
                              options={[
                                 {
                                    label: "Tous",
                                    value: "undefined",
                                 },
                                 {
                                    label: "En attente",
                                    value: EtatAvisEse.ETAT_EN_ATTENTE,
                                    icon: <HourglassOutlined className="text-warning" />,
                                 },
                                 {
                                    label: "En cours",
                                    value: EtatAvisEse.ETAT_EN_COURS,
                                    icon: <CheckCircleFilled className="text-success" />,
                                 },
                                 {
                                    label: "Aucun",
                                    value: EtatAvisEse.ETAT_AUCUN,
                                 },
                              ]}
                           />
                        </Col>

                        <Col xs={24} sm={24} md={6}>
                           Décision d'établissement
                        </Col>
                        <Col xs={24} sm={24} md={18}>
                           <Select
                              allowClear
                              className="w-100"
                              placeholder="Tous les états"
                              value={props.filtreBeneficiaire.etatDecisionAmenagement}
                              onChange={(value) => {
                                 props.setFiltreBeneficiaire((prev) => ({
                                    ...prev,
                                    etatDecisionAmenagement:
                                       value === "undefined" ? undefined : value,
                                    page: 1,
                                 }));
                              }}
                              options={[
                                 {
                                    label: "Tous les états",
                                    value: "undefined",
                                 },
                                 {
                                    label: "En attente validation par CAS",
                                    value: EtatDecisionEtablissement.ATTENTE_VALIDATION_CAS,
                                 },
                                 {
                                    label: "Validée, à éditer",
                                    value: EtatDecisionEtablissement.VALIDE,
                                 },
                                 {
                                    label: "Éditée",
                                    value: EtatDecisionEtablissement.EDITE,
                                 },
                              ]}
                           />
                        </Col>

                        <Col xs={24} sm={24} md={6}>
                           Composantes
                        </Col>
                        <Col xs={24} sm={24} md={18}>
                           <Select
                              allowClear
                              mode="tags"
                              className="w-100"
                              placeholder="Toutes les composantes"
                              value={props.filtreBeneficiaire["composante[]"]}
                              onChange={(value) => {
                                 props.setFiltreBeneficiaire((prev) => ({
                                    ...prev,
                                    "composante[]": value as string[],
                                    page: 1,
                                 }));
                              }}
                              options={(composantes?.items || []).map((c) => ({
                                 label: c.libelle,
                                 value: c["@id"],
                              }))}
                              optionFilterProp="label"
                           />
                        </Col>

                        <Col xs={24} sm={24} md={6}>
                           Formations
                        </Col>
                        <Col xs={24} sm={24} md={18}>
                           <Select
                              allowClear
                              mode="tags"
                              className="w-100"
                              placeholder="Toutes les formations"
                              value={props.filtreBeneficiaire["formation[]"]}
                              onChange={(value) => {
                                 props.setFiltreBeneficiaire((prev) => ({
                                    ...prev,
                                    "formation[]": value as string[],
                                    page: 1,
                                 }));
                              }}
                              options={(formations?.items || []).map((c) => ({
                                 label: `[${c.codeExterne?.replace("#", "-")}] ${c.libelle}`,
                                 value: c["@id"],
                              }))}
                              optionFilterProp="label"
                           />
                           <div className="legende">
                              Seules les formations ayant au moins un bénéficiaire sont proposées.
                           </div>
                        </Col>
                     </Row>
                  </>
               ),
            },
         ]}
      />
   );
}
