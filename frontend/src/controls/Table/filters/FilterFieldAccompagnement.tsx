/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { Col, Segmented } from "antd";
import { FiltreBeneficiaire } from "@controls/Table/BeneficiaireTable";
import { service } from "@lib";

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

interface FilterFieldAccompagnementProps {
  filtreBeneficiaire: FiltreBeneficiaire;
  setFiltreBeneficiaire: React.Dispatch<React.SetStateAction<FiltreBeneficiaire>>;
}

export function FilterFieldAccompagnement({
  filtreBeneficiaire,
  setFiltreBeneficiaire,
}: FilterFieldAccompagnementProps) {
  return (
    <>
      <Col xs={24} sm={24} md={6}>
        Accompagnement {service.sigle}
      </Col>
      <Col xs={24} sm={24} md={18}>
        <Segmented
          style={{ overflowX: "auto", maxWidth: "100%" }}
          value={booleanToString(filtreBeneficiaire["beneficiaires.avecAccompagnement"])}
          onChange={(value) => {
            setFiltreBeneficiaire((prev) => ({
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
    </>
  );
}
