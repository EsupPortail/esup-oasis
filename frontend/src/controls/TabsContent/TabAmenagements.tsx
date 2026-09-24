/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { DomaineAmenagementInfos, getAmenagementsByCategories } from "@lib";
import React, { useMemo } from "react";
import { IAmenagement, PREFETCH_CATEGORIES_AMENAGEMENTS, PREFETCH_TYPES_AMENAGEMENTS } from "@api";
import { CardAmenagement } from "@controls/Card/CardAmenagement";
import { Avatar, Empty, Flex, Row, Typography } from "antd";
import { useApi } from "@context/api/ApiProvider";
import { ModalAmenagement } from "@controls/Modals/ModalAmenagement";
import { useSearchParams } from "react-router-dom";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { ButtonAddAmenagement } from "@controls/TabsContent/ButtonAddAmenagement";

export function TabAmenagements(props: {
  utilisateurId: string;
  domaineAmenagement: DomaineAmenagementInfos;
}) {
  const screens = useBreakpoint();
  const [searchParams] = useSearchParams();
  const [editedAmenagement, setEditedAmenagement] = React.useState<IAmenagement>();
  const { data: typesAmenagements } = useApi().useGetFullCollection(PREFETCH_TYPES_AMENAGEMENTS);
  const { data: categoriesAmenagements } = useApi().useGetFullCollection(
    PREFETCH_CATEGORIES_AMENAGEMENTS,
  );
  const { data: amenagements } = useApi().useGetFullCollection({
    path: "/utilisateurs/{uid}/amenagements",
    parameters: {
      uid: props.utilisateurId,
    },
  });

  const amenagementsByCategories = useMemo(() => {
    return getAmenagementsByCategories(
      amenagements?.items || [],
      categoriesAmenagements?.items || [],
      typesAmenagements?.items || [],
      props.domaineAmenagement.id,
    );
  }, [
    amenagements?.items,
    categoriesAmenagements?.items,
    props.domaineAmenagement.id,
    typesAmenagements?.items,
  ]);

  return (
    <>
      <Flex justify="space-between" align="center" className="mt-1 mb-2" wrap>
        <Typography.Title level={3} className="mt-0 mb-0">
          <Avatar size="small" className={`mr-2 bg-${props.domaineAmenagement.couleur}`} />
          {props.domaineAmenagement.libelleLongPluriel}
        </Typography.Title>
        <div className={`text-right${!screens.lg ? " mt-2" : ""}`}>
          <ButtonAddAmenagement
            utilisateurId={props.utilisateurId}
            domaineAmenagement={props.domaineAmenagement}
          />
        </div>
      </Flex>

      <div>
        <ModalAmenagement
          open={!!editedAmenagement}
          setOpen={() => setEditedAmenagement(undefined)}
          amenagementId={editedAmenagement?.["@id"]}
          utilisateurId={props.utilisateurId}
          domaineAmenagement={props.domaineAmenagement}
        />

        <div>
          <Row gutter={[16, 16]}>
            {amenagementsByCategories?.map((c) => (
              <span
                key={c["@id"]}
                style={{ width: "100%", display: "contents" }}
                className={`border-color-${props.domaineAmenagement.couleur}`}
              >
                {c.typeAmenagements.map((ta) => (
                  <span key={ta["@id"]} style={{ width: "100%", display: "contents" }}>
                    {ta.amenagements.map((a) => (
                      <CardAmenagement 
                        couleur={props.domaineAmenagement.couleur} 
                        categorie={c.libelle!} 
                        amenagement={a} 
                        type={ta.libelle}
                        onClickEdit={ setEditedAmenagement }>
                      </CardAmenagement>
                    ))}
                  </span>
                ))}
              </span>
            ))}
          </Row>
        </div>
        {amenagementsByCategories?.length === 0 && (
          <Empty
            image={Empty.PRESENTED_IMAGE_SIMPLE}
            className="mt-3 mb-1"
            description="Aucun aménagement"
          />
        )}
      </div>
    </>
  );
}
