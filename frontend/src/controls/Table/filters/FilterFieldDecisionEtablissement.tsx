/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Col, Select } from "antd";
import { FiltreBeneficiaire } from "@controls/Table/BeneficiaireTable";
import { EtatDecisionEtablissement } from "@controls/Avatars/DecisionEtablissementAvatar";
import { decisionEtab } from "@lib";

interface FilterFieldDecisionEtablissementProps {
  filtreBeneficiaire: FiltreBeneficiaire;
  setFiltreBeneficiaire: React.Dispatch<React.SetStateAction<FiltreBeneficiaire>>;
}

export function FilterFieldDecisionEtablissement({
  filtreBeneficiaire,
  setFiltreBeneficiaire,
}: FilterFieldDecisionEtablissementProps) {
  return (
    <>
      <Col xs={24} sm={24} md={6}>
        {decisionEtab.Denomination}
      </Col>
      <Col xs={24} sm={24} md={18}>
        <Select
          allowClear
          className="w-100"
          placeholder="Tous les états"
          value={filtreBeneficiaire.etatDecisionAmenagement}
          onChange={(value) => {
            setFiltreBeneficiaire((prev) => ({
              ...prev,
              etatDecisionAmenagement: value === "undefined" ? undefined : value,
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
    </>
  );
}
