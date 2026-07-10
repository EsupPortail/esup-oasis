/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Alert, Breadcrumb, Empty, Form, Layout, Space, Spin, Typography } from "antd";
import { NavLink } from "react-router-dom";
import { HomeFilled } from "@ant-design/icons";
import React, { useEffect, useState } from "react";
import { capitalize } from "@utils/string";
import { useApi } from "@context/api/ApiProvider";
import { ApiPathMethodQuery, IPeriode, PREFETCH_CAMPUS, PREFETCH_TYPES_EVENEMENTS } from "@api";
import { PARAMETRE_COEF_COUT_CHARGE } from "@/constants";
import { montantToString, to2Digits } from "@utils/number";
import {
  BilanFilterForm,
  BilanFilterValues,
} from "@routes/administration/Bilans/BeneficiairesIntervenants/components/BilanFilterForm";
import {
  BilanTable,
  IActivite,
} from "@routes/administration/Bilans/BeneficiairesIntervenants/components/BilanTable";
import { IActiviteExport } from "@routes/administration/Bilans/BeneficiairesIntervenants/components/BilanExportButton";

type FiltreBilanIntervenant = NonNullable<ApiPathMethodQuery<"/suivis/intervenants", "get">>;
type FiltreBilanBeneficiaire = NonNullable<ApiPathMethodQuery<"/suivis/beneficiaires", "get">>;
type FiltreBilan = FiltreBilanBeneficiaire | FiltreBilanIntervenant;

/**
 * Compute the activity report for beneficiaries or intervenants.
 *
 * @param {object} props - The props object.
 * @param {string} props.type - The type of activity report: "bénéficiaire" or "intervenant".
 *
 * @return {React.ReactElement} - The activity report component.
 */
export default function BilanBeneficiaireIntervenant(props: {
  type: "bénéficiaire" | "intervenant";
}): React.ReactElement {
  const [form] = Form.useForm();
  const [submitted, setSubmitted] = useState(false);
  const [filtre, setFiltre] = useState<FiltreBilan>();
  const [data, setData] = useState<IActiviteExport[]>();
  const { data: campus, isFetching: isFetchingCampus } =
    useApi().useGetFullCollection(PREFETCH_CAMPUS);
  const { data: typesEvenements, isFetching: isFetchingTypesEvenements } =
    useApi().useGetFullCollection(PREFETCH_TYPES_EVENEMENTS);
  const { data: coef } = useApi().useGetItem({
    path: "/parametres/{cle}",
    url: PARAMETRE_COEF_COUT_CHARGE,
  });

  const {
    data: dataRawBenef,
    isFetching: isFetchingBenef,
    isError: isErrorBenef,
  } = useApi().useGetCollection({
    path: "/suivis/beneficiaires",
    query: props.type === "bénéficiaire" ? (filtre as FiltreBilanBeneficiaire) : undefined,
    enabled: !!filtre && props.type === "bénéficiaire",
  });
  const {
    data: dataRawInterv,
    isFetching: isFetchingInterv,
    isError: isErrorInterv,
  } = useApi().useGetCollection({
    path: "/suivis/intervenants",
    query: props.type === "intervenant" ? (filtre as FiltreBilanIntervenant) : undefined,
    enabled: !!filtre && props.type === "intervenant",
  });
  const dataRaw = props.type === "bénéficiaire" ? dataRawBenef : dataRawInterv;
  const isFetching = props.type === "bénéficiaire" ? isFetchingBenef : isFetchingInterv;
  const isError = props.type === "bénéficiaire" ? isErrorBenef : isErrorInterv;

  useEffect(() => {
    if (dataRaw && coef?.valeursCourantes && coef?.valeursCourantes.length === 1) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setData(
        dataRaw.items.map((item) => ({
          nom: item.utilisateur?.nom?.toLocaleUpperCase(),
          prenom: item.utilisateur?.prenom,
          email: item.utilisateur?.email,
          campus: campus?.items.find((c) => c["@id"] === item.campus)?.libelle,
          "categorie evenement": typesEvenements?.items.find((c) => c["@id"] === item.type)
            ?.libelle,
          nbEvenements: item.nbEvenements,
          nbHeures: to2Digits(item.nbHeures),
          "taux horaire": to2Digits(item.tauxHoraire?.montant),
          montant: montantToString(item.nbHeures, item.tauxHoraire?.montant),
          "cout charge": montantToString(
            item.nbHeures,
            item.tauxHoraire?.montant,
            coef.valeursCourantes?.[0].valeur ?? undefined,
          ),
        })),
      );
    }
  }, [campus?.items, coef?.valeursCourantes, dataRaw, typesEvenements?.items]);

  function buildFiltre(values: BilanFilterValues) {
    const filtres: FiltreBilan = {
      "campus[]": values["campus[]"],
      "type[]": values["type[]"],
    };

    if (values["periode[]"] && values["periode[]"].length > 0) {
      filtres["periode[]"] = values["periode[]"].map((p: IPeriode) => p["@id"] as string);
    }

    if (values.utilisateur) {
      if (props.type === "bénéficiaire") {
        ((filtres as FiltreBilanBeneficiaire) || {}).beneficiaires = values.utilisateur;
      } else {
        ((filtres as FiltreBilanIntervenant) || {}).intervenant = values.utilisateur;
      }
    }

    setSubmitted(true);
    setFiltre(filtres);
  }

  return (
    <Layout.Content className="administration" style={{ padding: "0 50px" }}>
      <Breadcrumb
        className="mt-2"
        items={[
          {
            key: "bilans",
            title: (
              <NavLink to="/bilans">
                <Space>
                  <HomeFilled />
                  Bilans
                </Space>
              </NavLink>
            ),
          },
          {
            key: "utilisateur",
            title: `${capitalize(props.type)}s`,
          },
        ]}
      />
      <Typography.Title level={1}>Bilans</Typography.Title>
      <Typography.Title level={2} className="mt-0 mb-4">
        Suivi de l'activité des {`${capitalize(props.type)}s`}
      </Typography.Title>

      <BilanFilterForm
        type={props.type}
        form={form}
        onFinish={buildFiltre}
        onValuesChange={() => setSubmitted(false)}
        isFetching={isFetching}
        isFetchingCampus={isFetchingCampus}
        isFetchingTypesEvenements={isFetchingTypesEvenements}
        campusOptions={campus?.items.map((c) => ({
          label: c.libelle + (c.actif ? "" : " (désactivé)"),
          value: c["@id"] as string,
        }))}
        typesEvenementsOptions={typesEvenements?.items.map((c) => ({
          label: c.libelle,
          value: c["@id"] as string,
          className: `text-${c.couleur}-dark`,
        }))}
        submitted={submitted}
        data={data}
        totalItems={dataRaw?.totalItems}
      />

      {submitted && isFetching && !dataRaw && (
        <Spin size="large" style={{ display: "block", marginTop: 24, textAlign: "center" }} />
      )}

      {submitted && isError && (
        <Alert
          className="mt-2"
          type="error"
          showIcon
          title="Erreur lors du chargement des données"
          description="Une erreur est survenue lors de la récupération des données. Veuillez réessayer."
        />
      )}

      {submitted && !isError && dataRaw && dataRaw.totalItems === 0 && (
        <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} className="mt-2" />
      )}

      {submitted && dataRaw && dataRaw.totalItems > 0 && (
        <BilanTable type={props.type} loading={isFetching} data={dataRaw.items as IActivite[]} />
      )}
    </Layout.Content>
  );
}
