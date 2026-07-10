/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useApi } from "@context/api/ApiProvider";
import Spinner from "@controls/Spinner/Spinner";
import { MinusOutlined } from "@ant-design/icons";
import { Space } from "antd";
import { ComposanteItem } from "./ComposanteItem";
import React from "react";
import { IInscription } from "@api";
import { EllipsisMiddle } from "@controls/Typography/EllipsisMiddle";

export function InscriptionItem(props: {
  utilisateurId?: string | undefined;
  inscription?: IInscription | undefined;
}) {
  const { data, isFetching } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: props.utilisateurId,
    enabled: !!props.utilisateurId,
  });
  const item = data
    ? [...(data.inscriptions || [])].sort((i1, i2) =>
        (i2.debut || "").localeCompare(i1.debut || ""),
      )[0]
    : props.inscription;

  if (!props.utilisateurId) return null;
  if (isFetching) return <Spinner />;
  if (!data) return <MinusOutlined />;

  if (!item) return <MinusOutlined />;

  return (
    <Space orientation="vertical" size={2}>
      <ComposanteItem composanteId={item.formation?.composante} />
      <EllipsisMiddle
        className="light"
        style={{ maxWidth: 300 }}
        suffixCount={12}
        content={item.formation?.libelle as string}
        expandable
      />
    </Space>
  );
}
