/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useEffect, useState } from "react";
import { useApi } from "@context/api/ApiProvider";
import { NB_MAX_ITEMS_PER_PAGE } from "@/constants";
import { Avatar, Drawer, Input, List } from "antd";
import ListSelectable from "@controls/Forms/ListSelectable/ListSelectable";
import { UserOutlined } from "@ant-design/icons";
import { IUtilisateur } from "@api";
import { service } from "@lib";

interface IUtilisateurSearchProps {
  visible: boolean;
  setVisible: (visible: boolean) => void;
  onSelected: (utilisateur: IUtilisateur | undefined) => void;
}

/**
 * Drawer to performs a search for users based on a provided term.
 *
 * @param {IUtilisateurSearchProps} props - The component props.
 * @param {boolean} props.visible - Whether the search component is visible.
 * @param {function} props.setVisible - Function to set the visibility of the search component.
 * @param {function} props.onSelected - Function to handle selection of a user.
 * @returns {ReactElement} The search component JSX element.
 */
export function UtilisateurSearch({
  visible,
  setVisible,
  onSelected,
}: IUtilisateurSearchProps): ReactElement {
  const [recherche, setRecherche] = useState("");
  const { data, isFetching, refetch } = useApi().useGetCollectionPaginated({
    path: "/utilisateurs",
    page: 1,
    itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
    query: {
      term: recherche,
    },
    enabled: recherche.length > 2,
  });

  const handleClose = () => {
    setRecherche("");
    setVisible(false);
  };

  useEffect(() => {
    if (recherche.length > 2) refetch().then();
  }, [refetch, recherche]);

  return (
    <Drawer
      destroyOnHidden
      open={visible}
      onClose={handleClose}
      title={`Ajouter un utilisateur ${service.a}`}
    >
      <Input.Search
        placeholder="Rechercher un utilisateur"
        onSearch={(value) => {
          setRecherche(value);
        }}
        loading={isFetching}
      />

      {data?.items && (
        <ListSelectable<IUtilisateur>
          loading={isFetching}
          items={data.items}
          classNameSelected="bg-beneficiaire-light"
          onSelect={(item) => {
            onSelected(item);
          }}
          header={<div className="semi-bold">Utilisateurs trouvés</div>}
          renderItem={(item) => (
            <List.Item.Meta
              className="meta"
              avatar={<Avatar icon={<UserOutlined />} />}
              title={`${item.prenom} ${item.nom?.toLocaleUpperCase()}`}
              description={item.email || "Pas d'email"}
            />
          )}
        />
      )}
    </Drawer>
  );
}
